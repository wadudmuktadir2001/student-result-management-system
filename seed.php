<?php

if (PHP_SAPI !== 'cli') {
    exit('Run this file from the terminal only.');
}

require 'bootstrap.php';

if (q('SELECT COUNT(*) FROM users')->fetchColumn()) {
    exit("Database already has users; seed skipped.\n");
}

$db->beginTransaction();

try {
    foreach (
        [
            ['Campus Admin', 'admin@demo.test', 'admin'],
            ['Dr. Sarah Ahmed', 'teacher@demo.test', 'teacher'],
            ['Arif Rahman', 'student@demo.test', 'student'],
            ['Nadia Rahman', 'parent@demo.test', 'parent'],
            ['Maya Islam', 'maya@demo.test', 'student'],
        ] as $u
    ) {
        q(
            'INSERT INTO users(name,email,role,password) VALUES(?,?,?,?)',
            [
                $u[0],
                $u[1],
                $u[2],
                password_hash('Demo@12345', PASSWORD_DEFAULT),
            ]
        );
    }

    q(
        "INSERT INTO students
         VALUES(3,'ST-2026-001',4),(5,'ST-2026-002',NULL)"
    );

    q(
        "INSERT INTO courses(code,name,credits)
         VALUES
         ('CSE101','Programming Fundamentals',3),
         ('MAT101','Mathematics',3),
         ('ENG101','English Communication',2)"
    );

    q(
        "INSERT INTO semesters(name)
         VALUES('Spring 2026'),('Summer 2026')"
    );

    foreach ([1, 2] as $s) {
        foreach ([1, 2, 3] as $c) {
            q(
                'INSERT INTO assignments(teacher_id,course_id,semester_id)
                 VALUES(2,?,?)',
                [$c, $s]
            );

            foreach ([3, 5] as $st) {
                q(
                    'INSERT INTO enrollments VALUES(?,?,?)',
                    [$st, $c, $s]
                );

                $m = ($st === 3 ? 68 : 74) + $s * 4 + $c;

                q(
                    "INSERT INTO results(
                        student_id,
                        course_id,
                        semester_id,
                        marks,
                        comment,
                        published,
                        updated_by
                     )
                     VALUES(
                        ?,?,?,?,
                        'Good progress. Keep practising.',
                        1,2
                     )",
                    [$st, $c, $s, $m]
                );

                $rid = $db->lastInsertId();

                q(
                    'INSERT INTO audit_logs(result_id,actor_id,new_data)
                     VALUES(?,2,?)',
                    [
                        $rid,
                        json_encode([
                            'marks' => $m,
                            'published' => 1,
                            'seed' => true,
                        ]),
                    ]
                );

                foreach ([1, 2, 3, 4, 5] as $d) {
                    q(
                        'INSERT INTO attendance VALUES(?,?,?,?,?)',
                        [
                            $st,
                            $c,
                            $s,
                            '2026-'
                                . ($s === 1 ? '03' : '07')
                                . '-'
                                . sprintf('%02d', $d),
                            $d === 5 ? 'absent' : 'present',
                        ]
                    );
                }
            }
        }
    }

    q(
        "INSERT INTO notifications(parent_id,message)
         VALUES(
             4,
             'New results for Arif Rahman are available. Open Results to view the report card.'
         )"
    );

    $db->commit();

    echo "Demo seeded. Password: Demo@12345\n";
} catch (Throwable $e) {
    $db->rollBack();

    throw $e;
}
