<?php

/**
 * Runs a PHP snippet in a separate OS process and captures its output.
 */
final class ProcessRunner
{
    /**
     * @return array{stdout: string, stderr: string, exit: int}
     */
    public static function run(string $phpCode): array
    {
        $process = proc_open(
            [PHP_BINARY, '-r', $phpCode],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Could not start PHP subprocess.');
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exit = proc_close($process);

        return [
            'stdout' => $stdout === false ? '' : $stdout,
            'stderr' => $stderr === false ? '' : $stderr,
            'exit' => $exit,
        ];
    }

    /**
     * Returns a PHP expression evaluating to the Composer autoloader path,
     * for use inside the subprocess snippet.
     */
    public static function autoload(): string
    {
        return var_export(__DIR__ . '/../../vendor/autoload.php', true);
    }
}
