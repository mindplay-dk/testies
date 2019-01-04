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

    $is->eq("line 1\nline 2\nline 3", "line 1\nline 3\nline 4"); // non-equal multi-line strings
    $is->eq("line 1\nline 2\nline 3", "line 1\nline 3\nline 4", "why"); // non-equal multi-line strings + why

//                $is->eq(format([1, 2, 3]), "array[3]");
//                $is->eq(format(true), "TRUE");
//                $is->eq(format(false), "FALSE");
//                $is->eq(format(new Foo), "Foo");

//    $is->eq(invoke(new Foo, "blip"), "blip");
//
//    $is->eq(inspect(new Foo, "bar"), "blip");

//                expect(
//                    RuntimeException::class,
//                    "why",
//                    function () {
//                        throw new RuntimeException("boom"); // succeeds
//                    }
//                );
//
//                expect(
//                    RuntimeException::class,
//                    "why",
//                    function () {
//                        throw new RuntimeException("booooooom");
//                    },
//                    "/bo+m/" // succeeds
//                );
//
//                expect(
//                    RuntimeException::class,
//                    "why",
//                    function () {
//                        throw new RuntimeException("bam");
//                    },
//                    "/bo+m/" // fails
//                );
//
//                expect(
//                    RuntimeException::class,
//                    "why",
//                    function () {
//                        // doesn't throw
//                    }
//                );

//    throw new RuntimeException("THE END");
};
