<?php

namespace mindplay\testies;

/**
 * This class will launch the built-in server in PHP 5.4+ in the background
 * and clean it up after use.
 */
class TestServer
{
    /**
     * @var resource PHP server process handle
     */
    protected $proc;

    /**
     * @var resource[] indexed array of file-pointers for the open PHP server process
     */
    protected $pipes;

    /**
     * Launch the built-in PHP server as a child process.
     *
     * @param string|null $path absolute path to a root folder
     * @param int         $port local port number (defaults to 8000)
     * @param string|null $host local host name or IP (defaults to "127.0.0.1")
     */
    public function __construct($path = null, $port = 8000, $host = null)
    {
        if ($path === null) {
            $path = getcwd();
        }

        if ($host === null) {
            $host = '127.0.0.1';
        }

        $descriptorspec = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'a') // stderr
        );

        $cmd = "php -S {$host}:{$port} -t {$path}";

        echo $cmd;

        $this->proc = proc_open($cmd, $descriptorspec, $this->pipes);

        // TODO proper error handling
    }

    /**
     * Shut down the PHP server child process
     */
    public function __destruct()
    {
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);

        if (stripos(php_uname('s'), 'win') > -1) {
            $status = proc_get_status($this->proc);

            exec("taskkill /F /T /PID {$status['pid']}");
        } else {
            proc_terminate($this->proc);
        }

        proc_close($this->proc);
    }
}
