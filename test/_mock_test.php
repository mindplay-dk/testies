<?php

use mindplay\testies\Tester;

return function (Tester $is) {
    $is->ok(true);
    $is->ok(false);

    $is->ok(true, "why");
    $is->ok(false, "why");

    $is->ok(true, "why", "string");
    $is->ok(false, "why", "string");

    $is->ok(false, "why", "line 1\nline 2"); // multi-line string
    $is->ok(false, "why", [1,2,3]); // array

    $is->eq("string", "string"); // equal values
    $is->eq("string", "string", "why"); // equal values + why

    $is->eq("string 1", "string 2"); // non-equal values
    $is->eq("string 1", "string 2", "why"); // non-equal values + why

    $is->eq("line 1\nline 2\nline 3", "line 1\nline 2\nline 3"); // equal multi-line strings
    $is->eq("line 1\nline 2\nline 3", "line 1\nline 2\nline 3", "why"); // equal multi-line strings + why

    $is->eq("line 1\nline 2\nline 3\nline 4", "line 1\nline 4\nline 5"); // non-equal multi-line strings
    $is->eq("line 1\nline 2\nline 3", "line 1\nline 3\nline 4", "why"); // non-equal multi-line strings + why

    $is->eq(
        (object) [
            "foo" => ["line 1\nline 2"],
            "bar" => ["line 1\nline 2"]
        ],
        (object) [
            "foo" => ["line 2\nline 3"],
            "bar" => ["line 1\nline 2"]
        ],
        "why"
    );

    $is->expect(
        RuntimeException::class,
        "why",
        function () {
            throw new RuntimeException("boom"); // succeeds
        }
    );

    $is->expect(
        RuntimeException::class,
        "why",
        function () {
            throw new RuntimeException("booooooom");
        },
        "/bo+m/" // succeeds
    );

    $is->expect(
        RuntimeException::class,
        "why",
        function () {
            throw new RuntimeException("bam");
        },
        "/bo+m/" // fails
    );

    $is->expect(
        RuntimeException::class,
        "why",
        function () {
            // doesn't throw
        }
    );

    throw new RuntimeException("THE END");
};
