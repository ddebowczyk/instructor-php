<?php
namespace Cognesy\InstructorHub\Services;

use Cognesy\InstructorHub\Core\Cli;
use Cognesy\InstructorHub\Data\ErrorEvent;
use Cognesy\InstructorHub\Utils\Color;
use Exception;

class Runner
{
    public int $correct = 0;
    public int $incorrect = 0;
    public int $total = 0;
    /** @var ErrorEvent[] */
    public array $errors;
    private string $baseDir;

    public function __construct(
        public Examples $examples,
        public bool     $displayErrors,
        public int      $stopAfter,
        public bool     $stopOnError,
    ) {
        $this->baseDir = $this->examples->getBaseDir();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function runSingle($file) : void {
        $this->runFile($file);
        $this->displayErrors();
    }

    public function runAll() : void {
        $this->examples->forEachFile(function($file, $index) {
            return $this->runFile($file);
        });
        $this->stats();
        $this->displayErrors();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function runFile(mixed $file) : bool {
        // execute run.php and print the output to CLI
        Cli::grid([[3, "[.]", STR_PAD_LEFT, Color::DARK_GRAY]]);
        Cli::grid([[30, $file, STR_PAD_RIGHT, Color::WHITE]]);
        Cli::grid([[13, "> running ...", STR_PAD_RIGHT, Color::DARK_GRAY]]);
        $output = $this->execute($this->baseDir, $file);
        // process output
        return $this->processOutput($output, $file);
    }

    private function execute(string $dir, string $file) : string {
        ob_start();
        try {
            $path = 'php ' . $dir . '/' . $file . '/run.php 2>&1';
            $output = shell_exec($path);
        } catch (Exception $e) {
            $output = $e->getMessage();
        }
        $bufferedOutput = ob_get_contents();
        ob_end_clean();
        return $output . $bufferedOutput;
    }

    private function processOutput(string $output, string $file) : bool {
        Cli::grid([[1, ">", STR_PAD_RIGHT, Color::DARK_GRAY]]);
        if (strpos($output, 'Fatal error') !== false) {
            $this->errors[$file][] = new ErrorEvent($file, $output);
            Cli::grid([[5, "ERROR", STR_PAD_LEFT, Color::RED]]);
            Cli::outln();
            $this->incorrect++;
            if ($this->stopOnError) {
                Cli::out("[!] ", Color::DARK_YELLOW);
                Cli::outln("Terminating - error encountered...", Color::YELLOW);
                return false;
            }
        } else {
            Cli::grid([[5, "OK", STR_PAD_RIGHT, Color::GREEN]]);
            Cli::outln();
            $this->correct++;
        }
        $this->total++;
        if (($this->stopAfter > 0) && ($this->total >= $this->stopAfter)) {
            Cli::out("[!] ", Color::DARK_YELLOW);
            Cli::outln("Terminating - set limit reached...", Color::YELLOW);
            return false;
        }
        return true;
    }

    public function stats() : void {
        $correctPercent = $this->percent($this->correct, $this->total);
        $incorrectPercent = $this->percent($this->incorrect, $this->total);
        Cli::outln();
        Cli::outln();
        Cli::outln("RESULTS:", [Color::YELLOW, Color::BOLD]);
        Cli::out("[+]", Color::GREEN);
        Cli::outln(" Correct runs ..... $this->correct ($correctPercent%)");
        Cli::out("[-]", Color::RED);
        Cli::outln(" Incorrect runs ... $this->incorrect ($incorrectPercent%)");
        Cli::outln("Total ................ $this->total (100%)", [Color::BOLD, Color::WHITE]);
        Cli::outln();
    }

    private function displayErrors() {
        if ($this->displayErrors && !empty($this->errors)) {
            Cli::outln();
            Cli::outln();
            Cli::outln("ERRORS:", [Color::YELLOW, Color::BOLD]);
            foreach ($this->errors as $file => $group) {
                Cli::outln("[$file]", Color::DARK_YELLOW);
                foreach ($group as $error) {
                    Cli::outln('---', Color::DARK_YELLOW);
                    Cli::margin($error->output, 4, Color::RED, Color::GRAY);
                    Cli::outln();
                }
            }
        }
    }

    private function percent(int $value, int $total) : int {
        return ($total == 0) ? 0 : round(($value / $total) * 100, 0);
    }
}