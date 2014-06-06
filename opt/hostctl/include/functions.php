<?php

spl_autoload_register('hostctl_autoloader');

/**
 * Simple autoloader implementation
 *
 * @param string $className
 */
function hostctl_autoloader($className)
{
    $path = __DIR__ . '/' . strtr($className, '\\', '/') . '.php';

    if (is_file($path) && is_readable($path)) {
        require $path;
    }
}

/**
 * Load config from the specified ini file, merged with supplied defaults
 *
 * @param string $file
 * @param array $conf
 * @return array
 */
function hostctl_load_config($file, array $conf)
{
    if (is_file($file) && is_readable($file)) {
        $conf = array_merge($conf, (array) parse_ini_file($file));
    }

    return $conf;
}

/**
 * exec() implementation that allows data to be passed to STDIN and returns the STDOUT/STDERR output separately
 *
 * @param string $cmd
 * @param string $stdIn
 * @return array
 */
function exec_plus($cmd, $stdIn = null) {
    $proc = proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);

    if (isset($stdIn)) {
        fwrite($pipes[0], (string) $stdIn);
    }
    fclose($pipes[0]);

    $stdOut = stream_get_contents($pipes[1]);
    $stdErr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = (int) proc_get_status($proc)['exitcode'];
    proc_close($proc);

    return [$exitCode, $stdOut, $stdErr];
}

/**
 * Remove a directory and all it's contents recursively
 *
 * @param string $dir
 * @return bool
 */
function rmdir_plus($dir)
{
    if (!is_dir($dir)) {
        return false;
    }

    foreach (glob($dir . '/*') as $file) {
        $func = is_dir($file) ? 'rmdir_plus' : 'unlink';

        if (!$func($file)) {
            return false;
        }
    }

    return rmdir($dir);
}

/**
 * Render a template string
 *
 * If a stream is passed to $output, rendered data is written to that stream and the length of the written
 * data is returned. If any other value except NULL is passed, it is treated as a file path, the rendered
 * data is written to this file and the length of the written data is returned. If NULL is passed or no value
 * is supplied, the rendered template is returned as a string.
 *
 * @param string $template
 * @param array|object $vars
 * @param resource|string $output
 * @return int|string
 */
function render_template($template, $vars, $output = null) {
    $vars = (array) $vars;

    $rendered = preg_replace_callback('/\\\\\\\\(?=\\\\*+{)|\\\\{|{(\w+)}/', function($match) use($vars) {
        if ($match[0][0] === '\\') {
            $result = $match[0][1];
        } else if (isset($vars[$match[1]])) {
            $result = $vars[$match[1]];
        } else {
            $result = $match[0];
        }

        return $result;
    }, $template);

    if (isset($output)) {
        if (is_resource($output)) {
            return fwrite($output, $rendered);
        }

        return file_put_contents($output, $rendered);
    }

    return $rendered;
}

/**
 * Render a template file
 *
 * @param string $path
 * @param array|object $vars
 * @param resource|string $output
 * @return int|string
 */
function render_template_file($path, $vars, $output = null) {
    return render_template(file_get_contents($path), $vars, $output);
}

/**
 * Yay global state!
 *
 * I'm sorry.
 *
 * @param array $argv
 * @return array
 */
function fetch_args($argv = null) {
    static $args;

    if (isset($argv)) {
        $args = $argv;
    }

    return $args;
}
