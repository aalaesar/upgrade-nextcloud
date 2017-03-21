#!/usr/bin/env php
<?php
/**
 * Verify nextcloud instance versions and return rc 0 if upgrade can be performed
 *
 * Takes 2 arguments:
 * $1 : installed instance path
 * $2 : downloaded instance path
 *
 * Exit codes:
 * 0 : installed version can be upgraded
 * 1 : no upgrade required
 * 2 : installed version not upgradable with the downloaded one
 * 3 : bad parameters
 */

PHP_SAPI === 'cli' || die('CLI only');

// Remove filename from parameter array.
array_shift($argv);

// Check the parameters.
count($argv) === 2 || exit(3);
foreach ($argv as $arg) {
  if (!file_exists($arg . '/version.php')) {
    printf('"%s" is not a valid folder or does not contain version.php.', $arg);
    exit(3);
  }
}

$nc_install = ['path' => $argv[0], 'file' => $argv[0] . '/version.php'];
$nc_upgrade = ['path' => $argv[1], 'file' => $argv[1] . '/version.php'];

// Gather version information.
loadAll($nc_install);
loadAll($nc_upgrade);

checkVersions($nc_install, $nc_upgrade);
checkUpgradeRequirement($nc_install, $nc_upgrade);

// All good, exit with 0.
exit(0);

/**
 * Load all version information for the passed version
 *
 * @param array $v
 */
function loadAll(&$v)
{
  require $v['file'];

  loadVersion($v, $OC_Version);
  loadUpgradeFrom($v, $OC_VersionCanBeUpgradedFrom);
}

/**
 * Load the version information
 *
 * @param array $v
 * @param array $version
 */
function loadVersion(array &$v, array $version)
{
  $v['version_arr'] = $version;

  // Last number is used internally by NC and should not be part of the version.
  array_pop($version);
  $v['version'] = implode('.', $version);
}

/**
 * Load the upgrade version requirement
 *
 * @param array $v
 * @param array $version
 */
function loadUpgradeFrom(array &$v, array $version)
{
  if (array_key_exists('nextcloud', $version)) {
    $v['upgrade_from'] = min(array_keys(array_filter($version['nextcloud'])));
  } else {
    // Pre NC12 formatting.
    $v['upgrade_from'] = implode('.', $version);
  }
}

/**
 * Compare the versions and exit if upgrade not applicable
 *
 * @param array $install
 * @param array $upgrade
 */
function checkVersions(array $install, array $upgrade)
{
  $version_compare = version_compare($upgrade['version'], $install['version']);
  if ($version_compare === 0) {
    printf('Both versions are equal (v%s). Doing nothing.', $install['version']);
    exit(1);
  } elseif ($version_compare === -1) {
    printf('"%s" (v%s) is newer than v%s. Doing nothing.', $install['path'], $install['version'], $upgrade['version']);
    exit(1);
  }
}

/**
 * Check if the installed version meets the new version's upgrade requirement
 *
 * @param array $install
 * @param array $upgrade
 */
function checkUpgradeRequirement(array $install, array $upgrade)
{
  $version_upgrade_compare = version_compare($upgrade['upgrade_from'], $install['version']);
  if ($version_upgrade_compare === 1) {
    printf(
      '"%s" (v%s) cannot be upgraded to v%s (minimum v%s). Doing nothing.',
      $install['path'],
      $install['version'],
      $upgrade['version'],
      $upgrade['upgrade_from']
    );
    exit(2);
  }
}
