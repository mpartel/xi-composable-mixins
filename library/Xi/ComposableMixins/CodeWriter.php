<?php
namespace Xi\ComposableMixins;

/**
 * Writes generated files into files to a given directory.
 */
class CodeWriter
{
    protected $baseDir;

    public function __construct($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function save($className, $code)
    {
        $path = $this->toPath($className);
        $dir = dirname($path);
        @mkdir($dir, 0777, true);
        file_put_contents($path, "<?php\n" . $code, LOCK_EX);
    }

    protected function toPath($className)
    {
        $parts = explode('\\', ltrim($className, '\\'));
        return $this->baseDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
    }

    protected function readFileWithSharedLock($path)
    {
        $f = @fopen($path, "rb");
        if ($f !== false) {
            flock($f, LOCK_SH);
            $data = stream_get_contents($f);
            flock($f, LOCK_UN);
            fclose($f);
            return $data;
        } else {
            return null;
        }
    }
}