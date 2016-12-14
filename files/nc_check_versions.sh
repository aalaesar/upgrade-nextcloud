#!/bin/bash
#### Verify nextcloud instance versions and return rc 0 if upgrade can be performed
## take 2 arguments
# $1 installed instance path
# $2 downloaded instance path
# quick test of the parameters
# return :
# 0 : when installed version can be upgraded.
# 1 : no upgrade required
# 2 : Installed version not upgradable with the downloaded one
# 3 : bad parameters

function ncGetBuildTimestamp() {
  # read version.php as parameter and converts OC_Build in seconds
  unset oc_buildTimestamp
  local oc_buildTimestamp=$(date -d $(grep OC_Build $1 | sed "s/.*'\(.* \).*/\1/" ) +"%s")
  echo $oc_buildTimestamp
}

function ncGetVersion() {
  # read version.php as parameter and get the second parameter in bash array
  unset oc_Version
  local oc_Version=($(grep "$2 =" $1 | sed 's/.*array(\([0-9,,]*\).*/\1/'| tr , ' '))
  echo ${oc_Version[@]}
}

# checking the parameters
[ $# -ne 2 ] && exit 3
for param in $*; do
  if [ ! -d $param ] || [ ! -f "$param/version.php" ]; then
    echo "$param is not a valid folder or do not contain version.php." >&1
    exit 3; fi
done
ncInstalled="$1/version.php"
ncDownloaded="$2/version.php"
# gather versions information
ncInstalled_ts=$(ncGetBuildTimestamp $ncInstalled)
ncInstalled_version=($(ncGetVersion $ncInstalled "OC_Version"))
ncDownloaded_ts=$(ncGetBuildTimestamp $ncDownloaded)
ncDownloaded_version=($(ncGetVersion $ncDownloaded "OC_Version"))
ncDownloaded_VRequirement=($(ncGetVersion $ncDownloaded "OC_VersionCanBeUpgradedFrom"))

# if the installed version has a build date equal to  the downloaded version, then exit with rc 1
if [ $ncInstalled_ts -eq $ncDownloaded_ts ]; then
  echo "Both version are equal (v$(echo ${ncInstalled_version[@]} | tr ' ' .)), nothing to do." >&1
  exit 1
fi
# compare both version numbers and exit with RC 1 if downloaded version if less than installed.
for item in $(seq 0 $((${#ncInstalled_version[@]}-1))); do
  if [ ${ncDownloaded_version[item]} -gt ${ncInstalled_version[item]} ]; then
    break
  elif [ ${ncDownloaded_version[item]} -lt ${ncInstalled_version[item]} ]; then
    echo "$1 is newer than v$(echo ${ncDownloaded_version[@]}|tr ' ' .). Doing nothing." >&1
    exit 1
  fi
done
# checking if the installed version meet the new version requirements for upgrades :
for item in $(seq 0 $((${#ncDownloaded_VRequirement[@]}-1))); do
  if [ ${ncInstalled_version[item]} -gt ${ncDownloaded_VRequirement[item]} ]; then
    break
  elif [ ${ncInstalled_version[item]} -lt ${ncDownloaded_VRequirement[item]} ]; then
    echo "$1 (v$(echo ${ncInstalled_version[@]} | tr ' ' .)) cannot be upgraded to v$(echo ${ncDownloaded_version[@]}|tr ' ' .) (minimum v$(echo ${ncDownloaded_VRequirement[@]}|tr ' ' .)).  Doing nothing." >&1
    exit 2
  fi
done
exit 0
