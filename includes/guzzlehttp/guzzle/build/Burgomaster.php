<?php
class Burgomaster
{
    public $stageDir;
    public $projectRoot;
    private $sections = [];
    public function __construct($stageDir, $projectRoot = null)
    {
        $this->startSection('setting_up');
        $this->stageDir = $stageDir;
        $this->projectRoot = $projectRoot;
        if (!$this->stageDir || $this->stageDir == '/') {
            throw new \InvalidArgumentException('Invalid base directory');
        }
        if (is_dir($this->stageDir)) {
            $this->debug("Removing existing directory: $this->stageDir");
            echo $this->exec("rm -rf $this->stageDir");
        }
        $this->debug("Creating staging directory: $this->stageDir");
        if (!mkdir($this->stageDir, 0777, true)) {
            throw new \RuntimeException("Could not create {$this->stageDir}");
        }
        $this->stageDir = realpath($this->stageDir);
        $this->debug("Creating staging directory at: {$this->stageDir}");
        if (!is_dir($this->projectRoot)) {
            throw new \InvalidArgumentException(
                "Project root not found: $this->projectRoot"
            );
        }
        $this->endSection();
        $this->startSection('staging');
        chdir($this->projectRoot);
    }
    public function __destruct()
    {
        if ($this->sections) {
            $this->endSection();
        }
    }
    public function startSection($section)
    {
        $this->sections[] = $section;
        $this->debug('Starting');
    }
    public function endSection()
    {
        if ($this->sections) {
            $this->debug('Completed');
            array_pop($this->sections);
        }
    }
    public function debug($message)
    {
        $prefix = date('c') . ': ';
        if ($this->sections) {
            $prefix .= '[' . end($this->sections) . '] ';
        }
        fwrite(STDERR, $prefix . $message . "\n");
    }
    public function deepCopy($from, $to)
    {
        if (!is_file($from)) {
            throw new \InvalidArgumentException("File not found: {$from}");
        }
        $to = str_replace('//', '/', $this->stageDir . '/' . $to);
        $dir = dirname($to);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new \RuntimeException("Unable to create directory: $dir");
            }
        }
        if (!copy($from, $to)) {
            throw new \RuntimeException("Unable to copy $from to $to");
        }
    }
    public function recursiveCopy(
        $sourceDir,
        $destDir,
        $extensions = ['php']
    ) {
        if (!realpath($sourceDir)) {
            throw new \InvalidArgumentException("$sourceDir not found");
        }
        if (!$extensions) {
            throw new \InvalidArgumentException('$extensions is empty!');
        }
        $sourceDir = realpath($sourceDir);
        $exts = array_fill_keys($extensions, true);
        $iter = new \RecursiveDirectoryIterator($sourceDir);
        $iter = new \RecursiveIteratorIterator($iter);
        $total = 0;
        $this->startSection('copy');
        $this->debug("Starting to copy files from $sourceDir");
        foreach ($iter as $file) {
            if (isset($exts[$file->getExtension()])
                || $file->getBaseName() == 'LICENSE'
            ) {
                $toPath = str_replace($sourceDir, '', (string) $file);
                $toPath = $destDir . '/' . $toPath;
                $toPath = str_replace('//', '/', $toPath);
                $this->deepCopy((string) $file, $toPath);
                $total++;
            }
        }
        $this->debug("Copied $total files from $sourceDir");
        $this->endSection();
    }
    public function exec($command)
    {
        $this->debug("Executing: $command");
        $output = $returnValue = null;
        exec($command, $output, $returnValue);
        if ($returnValue != 0) {
            throw new \RuntimeException('Error executing command: '
                . $command . ' : ' . implode("\n", $output));
        }
        return implode("\n", $output);
    }
    public function createAutoloader($files = [], $filename = 'autoloader.php')
    {
        $sourceDir = realpath($this->stageDir);
        $iter = new \RecursiveDirectoryIterator($sourceDir);
        $iter = new \RecursiveIteratorIterator($iter);
        $this->startSection('autoloader');
        $this->debug('Creating classmap autoloader');
        $this->debug("Collecting valid PHP files from {$this->stageDir}");
        $classMap = [];
        foreach ($iter as $file) {
            if ($file->getExtension() == 'php') {
                $location = str_replace($this->stageDir . '/', '', (string) $file);
                $className = str_replace('/', '\\', $location);
                $className = substr($className, 0, -4);
                if (strpos($className, 'src\\') === 0
                    || strpos($className, 'lib\\') === 0
                ) {
                    $className = substr($className, 4);
                }
                $classMap[$className] = "__DIR__ . '/$location'";
                $this->debug("Found $className");
            }
        }
        $destFile = $this->stageDir . '/' . $filename;
        $this->debug("Writing autoloader to {$destFile}");
        if (!($h = fopen($destFile, 'w'))) {
            throw new \RuntimeException('Unable to open file for writing');
        }
        $this->debug('Writing classmap files');
        fwrite($h, "<?php\n\n");
        fwrite($h, "\$mapping = array(\n");
        foreach ($classMap as $c => $f) {
            fwrite($h, "    '$c' => $f,\n");
        }
        fwrite($h, ");\n\n");
        fwrite(
            $h,
            <<<EOT
spl_autoload_register(function (\$class) use (\$mapping) {
    if (isset(\$mapping[\$class])) {
        require \$mapping[\$class];
    }
}, true);
EOT
        );
        fwrite($h, "\n");
        $this->debug('Writing automatically included files');
        foreach ($files as $file) {
            fwrite($h, "require __DIR__ . '/$file';\n");
        }
        fclose($h);
        $this->endSection();
    }
    private function createStub($dest, $autoloaderFilename = 'autoloader.php')
    {
        $this->startSection('stub');
        $this->debug("Creating phar stub at $dest");
        $alias = basename($dest);
        $constName = str_replace('.phar', '', strtoupper($alias)) . '_PHAR';
        $stub  = "<?php\n";
        $stub .= "define('$constName', true);\n";
        $stub .= "require 'phar://$alias/{$autoloaderFilename}';\n";
        $stub .= "__HALT_COMPILER();\n";
        $this->endSection();
        return $stub;
    }
    public function createPhar(
        $dest,
        $stub = null,
        $autoloaderFilename = 'autoloader.php'
    ) {
        $this->startSection('phar');
        $this->debug("Creating phar file at $dest");
        $this->createDirIfNeeded(dirname($dest));
        $phar = new \Phar($dest, 0, basename($dest));
        $phar->buildFromDirectory($this->stageDir);
        if ($stub !== false) {
            if (!$stub) {
                $stub = $this->createStub($dest, $autoloaderFilename);
            }
            $phar->setStub($stub);
        }
        $this->debug("Created phar at $dest");
        $this->endSection();
    }
    public function createZip($dest)
    {
        $this->startSection('zip');
        $this->debug("Creating a zip file at $dest");
        $this->createDirIfNeeded(dirname($dest));
        chdir($this->stageDir);
        $this->exec("zip -r $dest ./");
        $this->debug("  > Created at $dest");
        chdir(__DIR__);
        $this->endSection();
    }
    private function createDirIfNeeded($dir)
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException("Could not create dir: $dir");
        }
    }
}
