<?php

namespace osd84\BrutalTestRunner;

/*
 * Main
 */
class BrutalTestRunner
{
    public bool $debug = false;
    public int $tests_count = 0;
    public int $tests_failed_count = 0;
    public int $tests_success_count = 0;

    /**
     * define the log file to be used
     *
     * @param bool $debug
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }
    
     /**
     * AssertEqual - Only 1 and unique Assertion test system.
     *
     * @param mixed  $expect value you may have
     * @param mixed  $found  var you test
     * @param string $msg    general msg which explain what test is
     * @param bool   $strict if you want a strict test equality
     *
     * @return bool
     */
    public function assertEqual($expect, $found, string $msg, bool $strict = false): bool
    {
        $this->tests_count++;
        if ($strict && $expect === $found) {
            $this->tests_success_count++;
            print "test $this->tests_count ::s OK ✔ :: $msg\n";
            return true;
        }
        if (!$strict && $expect == $found) {
            $this->tests_success_count++;
            print "test $this->tests_count :: OK ✔ :: $msg\n";
            return true;
        }
        $this->tests_failed_count++;
        print "test $this->tests_count :: FAIL ✖ :: $msg\n";
        if ($this->debug) {
            print "    $expect != $found \n";
            print "---------------\nEXPECT :\n";
            var_export($expect);
            print "\nFOUND :\n";
            var_export($found);
            print "\n---------------\n";
            die('Tests FAILED');
        }
        return false;
    }



    /**
     * Only print a script Header tout output
     *
     * @param string $file
     */
    public function header(string $file): void
    {
        print "\n-----------\n";
        $fname = basename($file);
        print  "Brutal test Runner for [$fname]\n";
    }


    /**
     * Out script with summary and good exit code
     */
    public function footer($exit=true): void
    {
        print "\n-----------\n";
        if ($this->tests_failed_count) {
            print '✖ [FAILED] ';
            print "$this->tests_failed_count fails, $this->tests_success_count success, $this->tests_count total ";
            if ($exit) {
                exit(1);
            }
        }
        else {
            print '✔ [SUCCESS] ';
            print "$this->tests_failed_count fails, $this->tests_success_count success, $this->tests_count total ";
            if ($exit) {
                exit(0);
            }
        }
    }
}