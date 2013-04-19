<?php
namespace Xi;

use Xi\ComposableMixins\CodeWriter;

class CodeWriterTest extends \PHPUnit_Framework_TestCase
{
    private $codeDir;

    protected function setUp()
    {
        parent::setUp();

        $this->codeDir = dirname(dirname(__DIR__)) . '/composed_mixins';
        @mkdir($this->codeDir);

        ComposableMixins::setCodeWriter(new CodeWriter($this->codeDir));
    }

    protected function tearDown()
    {
        ComposableMixins::setCodeWriter(null);
        parent::tearDown();
    }

    public function test_file_code_cache()
    {
        $obj = ComposableMixins::instance('Xi\\SimpleTrait', 'Xi\\SimpleTraitOverride');
        $this->assertEquals('from SimpleTrait overridden', $obj->simple());

        $file1 = $this->codeDir . DIRECTORY_SEPARATOR . 'Xi' . DIRECTORY_SEPARATOR . 'SimpleTrait_class.php';
        $file2 = $this->codeDir . DIRECTORY_SEPARATOR . 'Xi' . DIRECTORY_SEPARATOR . 'SimpleTrait_class_with_Xi_SimpleTraitOverride.php';
        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        $expected1 =
            "<?php\n" .
            "namespace Xi;\n" .
            "class SimpleTrait_class { use SimpleTrait; }\n";
        $this->assertEquals($expected1, file_get_contents($file1));

        $expected2 =
            "<?php\n" .
            "namespace Xi;\n" .
            "class SimpleTrait_class_with_Xi_SimpleTraitOverride extends \Xi\SimpleTrait_class\n" .
            "{\n" .
            "    use \Xi\SimpleTraitOverride;\n" .
            "}\n";
        $this->assertEquals($expected2, file_get_contents($file2));
    }
}