<?php

/**
 * Class to test the version check script.
 */
class NcVersionCheckTest
{
  protected $upgrade_check_script;
  protected $install_dir;
  protected $upgrade_dir;
  protected $install_version_file;
  protected $upgrade_version_file;
  protected $install_version;
  protected $upgrade_version;
  protected $test_result;
  public static $version_files = [
    8  => '<?php $OC_Version = array(8, 2, 7, 1);  $OC_VersionCanBeUpgradedFrom = array(8, 1);',
    9  => '<?php $OC_Version = array(9, 0, 57, 2); $OC_VersionCanBeUpgradedFrom = array(8, 2);',
    10 => '<?php $OC_Version = array(10, 0, 4, 2); $OC_VersionCanBeUpgradedFrom = array(9, 0);',
    11 => '<?php $OC_Version = array(11, 0, 2, 7); $OC_VersionCanBeUpgradedFrom = array(9, 1);',
    12 => '<?php 
        $OC_Version = array(12, 0, 0, 13);
        $OC_VersionCanBeUpgradedFrom = [
          \'nextcloud\' => [
            \'11.0\' => true,
            \'12.0\' => true,
          ],
          \'owncloud\' => [
            \'10.0\' => true,
          ],
        ];',
  ];

  /**
   * NcVersionCheckTest constructor.
   *
   * @param array $argv
   */
  public function __construct($argv = [])
  {
    // Make sure the upgrade script is available.
    $this->upgrade_check_script = isset($argv[1])
      ? realpath($argv[1])
      : realpath(__DIR__ . '/../files/nc_check_versions.php');
    $this->upgrade_check_script || die('Hmm, weirdness is going down... Script not found.');

    $this->install_dir          = $this->getTempDir(null, 'nca_install_');
    $this->upgrade_dir          = $this->getTempDir(null, 'nca_upgrade_');
    $this->install_version_file = $this->install_dir . '/version.php';
    $this->upgrade_version_file = $this->upgrade_dir . '/version.php';
  }

  /**
   * Remove all temporary files and directories.
   */
  public function __destruct()
  {
    @unlink($this->upgrade_version_file);
    @unlink($this->install_version_file);
    @rmdir($this->upgrade_dir);
    @rmdir($this->install_dir);
  }

  /**
   * Create a temporary directory and return its path.
   *
   * @param string $dir
   * @param string $prefix
   * @param int    $mode
   *
   * @return string
   */
  protected function getTempDir($dir = null, $prefix = '', $mode = 0700)
  {
    empty($dir) && $dir = sys_get_temp_dir();
    $dir = rtrim($dir, '/') . '/';

    do {
      $path = $dir . $prefix . mt_rand(0, 9999999);
    } while (!@mkdir($path, $mode) && !is_dir($path));

    return $path;
  }

  /**
   * Write the version.php files.
   *
   * @param int $install_version
   * @param int $upgrade_version
   */
  protected function writeVersionFiles($install_version, $upgrade_version)
  {
    // If the version is invalid, just write an empty file.
    file_put_contents($this->install_version_file, array_key_exists($install_version, self::$version_files)
      ? self::$version_files[$install_version]
      : '');
    file_put_contents($this->upgrade_version_file, array_key_exists($upgrade_version, self::$version_files)
      ? self::$version_files[$upgrade_version]
      : '');
  }

  /**
   * Test the upgrade check script!
   *
   * @param int $install_version
   * @param int $upgrade_version
   *
   * @return \NcVersionCheckTest
   */
  public function testVersionCheck($install_version = null, $upgrade_version = null)
  {
    $this->install_version = $install_version;
    $this->upgrade_version = $upgrade_version;

    $this->writeVersionFiles($install_version, $upgrade_version);

    $command = sprintf('php %s %s %s',
      escapeshellarg($this->upgrade_check_script),
      $install_version ? escapeshellarg($this->install_dir) : '',
      $upgrade_version ? escapeshellarg($this->upgrade_dir) : ''
    );
    exec($command, $output, $exitcode);
    $output = implode($output);

    $this->test_result = compact('command', 'output', 'exitcode');

    unset($command, $output, $exitcode);

    return $this;
  }

  /**
   * Assert the test result or throw an Exception.
   *
   * @param int    $expected_exitcode
   * @param string $expected_output
   *
   * @return \NcVersionCheckTest
   * @throws \Exception
   */
  public function assert($expected_exitcode, $expected_output)
  {
    $msg = null;
    if ($this->test_result['exitcode'] !== $expected_exitcode) {
      $msg = sprintf('Failed asserting exitcode: %d instead of %d', $this->test_result['exitcode'], $expected_exitcode);
    } elseif ($this->test_result['output'] !== $expected_output && strpos($this->test_result['output'], $expected_output) === false) {
      $msg = sprintf('Failed asserting output: "%s" instead of "%s"', $this->test_result['output'], $expected_output);
    } else {
      return $this;
    }

    throw new Exception(sprintf(
      "Test failed (from %d to %d): %s\n%s\n",
      $this->install_version,
      $this->upgrade_version,
      $msg,
      json_encode($this->test_result)
    ));
  }
}

try {
  $t = new NcVersionCheckTest($argv);
  // Invalid parameters.
  $t->testVersionCheck()->assert(3, '');
  $t->testVersionCheck(9)->assert(3, '');
  // Same version.
  $t->testVersionCheck(9, 9)->assert(1, 'Both versions are equal');
  // Downgrade.
  $t->testVersionCheck(9, 8)->assert(1, 'is newer than');
  // Upgrade.
  $t->testVersionCheck(9, 10)->assert(0, '');
  // Upgrade (NC12+ format).
  $t->testVersionCheck(11, 12)->assert(0, '');
  // Invalid upgrade.
  $t->testVersionCheck(8, 10)->assert(2, 'cannot be upgraded');
} catch (Exception $e) {
  echo $e->getMessage();
  exit(1);
}
