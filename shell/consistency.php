<?php 

//DEFAULTS
$_defaults = array(
	'v'		=> false,
	'clean'	=> '..'. DIRECTORY_SEPARATOR .'..',
	'dirty'	=> '..',
	'comp'	=> '_COMPARE',
	'opts'	=> 'BE',
	'help'	=> false
);

//From Mage_Shell_Abstract
function _parseArgs()
{
	$current = null;
	$_args = array();
	foreach ($_SERVER['argv'] as $arg) {
		$match = array();
		if (preg_match('#^--([\w\d_-]{1,})$#', $arg, $match) || preg_match('#^-([\w\d_]{1,})$#', $arg, $match)) {
			$current = $match[1];
			$_args[$current] = true;
		} else {
			if ($current) {
				$_args[$current] = $arg;
			} else if (preg_match('#^([\w\d_]{1,})$#', $arg, $match)) {
				$_args[$match[1]] = true;
			}
		}
	}
	return $_args;
}

function showUsage()
{
	echo "USAGE: php -f consistency.php -- \n\t\t[-v version] [--clean clean_directory]\n\t\t[--dirty dirty_directory] [--opts diff_opts]\n\t\t[--comp compare_directory] [--help]\n";
	echo "PARAMETERS:\n";
	echo "\t-v\tThe version to check against.  EX: CE1.9.1.0 \tDefault: auto-detected version from Mage.php\n";
	echo "\t-clean\tThe directory clean versions can be found in. \tDefault: ..". DIRECTORY_SEPARATOR ."..". DIRECTORY_SEPARATOR ."{VERSION_STRING}\n";
	echo "\t-dirty\tThe directory the dirty version is found in. \tDefault: ..\n";
	echo "\t-comp\tThe directory to store the compare report. \tDefault: _COMPARE\n";
	echo "\t-opts\tThe options to pass the diff operation.  \tDefault: BE (diff always uses 'r')\n\n";
	echo "\t--help\tDisplays this help entry.\n";
}

function convertEdition($editionString)
{
	switch($editionString)
	{
		case 'Enterprise':
			return 'EE';
		case 'Community':
			return 'CE';
		case 'Professional':
			return 'PE';
		case 'Go':
			return 'GO';
		default:
			return '';
	}
}

$data = _parseArgs();

$use = array_merge($_defaults,$data);

if($use['help'])
{
	showUsage();
	exit;
}

if(!file_exists($use['comp']))
{
	echo 'Path not found to comparison report landing: '.$use['comp']."\n";exit;
}
if(!file_exists($use['comp']. DIRECTORY_SEPARATOR .'diffs'))
{
	echo 'Path not found to comparison report landing diffs: '.$use['comp']. DIRECTORY_SEPARATOR ."diffs\n";exit;
}

$dirtyDir = $use['dirty'];

if(!file_exists($dirtyDir))
{
	echo "Path not found to dirty version: $dirtyDir\n";exit;
}

if(!file_exists($dirtyDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php'))
{
	echo "Dirty directory does not apepar to be a Magento version: $dirtyDir\n";exit;
}

require_once $dirtyDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';

$detectedVersion = convertEdition(Mage::getEdition()).Mage::getVersion();
$version = $detectedVersion;
if($use['v'] !== false)
{
	$version = $use['v'];
}

$cleanDir = $use['clean'] . DIRECTORY_SEPARATOR . $version;

if(!file_exists($cleanDir))
{
	echo "Path not found to clean version: $cleanDir\n";exit;
}

if(!file_exists($cleanDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php'))
{
	echo "Clean directory does not appear to be a Magento version: $cleanDir\n";exit;
}

$diffDir = $use['comp']. DIRECTORY_SEPARATOR .'diffs';

$listFile = $use['comp'].'/files.txt';
echo 'Comparing '.$version." to targeted directory...\n";

$raw = shell_exec('diff -rq' . $use['opts'] .' '.$cleanDir.' '.$dirtyDir.' --strip-trailing-cr');

echo 'Scan complete, building report.'."\n";

$list = explode("\n",$raw);

$modFiles = array();
$newFiles = array();
$modImages = array();
$missingFiles = array();

foreach($list as $_diff)
{
	if(stripos($_diff,'Only in '.$dirtyDir) === 0)
	{
		$_diff = str_replace(array('Only in '.$dirtyDir,': '),array('','/'),$_diff);
		if(stripos($_diff,'/var') === 0 
			|| stripos($_diff,'/media') === 0 
			|| stripos($_diff,'/include') === 0)
		{
			//skip
			continue;
		}
		$newFiles[] = $_diff;
		continue;
	}
	
	if(stripos($_diff,'Only in '.$cleanDir) === 0)
	{
		$_diff = str_replace(array('Only in '.$cleanDir,': '),array('','/'),$_diff);
		$missingFiles[] = $_diff;
		continue;
	}
	
	if(stripos($_diff,'Files') === 0)
	{
		$_dPieces = explode(' and ',$_diff);
		
		$file = str_replace(array($dirtyDir,' differ'),array('',''),$_dPieces[1]);
		
		if(stripos($file,'.jpg') !== false ||
			stripos($file,'.jpeg') !== false ||
			stripos($file,'.gif') !== false ||
			stripos($file,'.png') !== false)
		{
			$modImages[] = $file;
			continue;
		}
		
		$modFiles[] = $file;
		continue;
	}
}

echo 'Found: '."\n";
echo "\t".count($modFiles).' Modified Core Files'."\n";
echo "\t".count($modImages).' Modified Images Files'."\n";
echo "\t".count($newFiles).' New Files'."\n";
echo "\t".count($missingFiles).' Missing Files.'."\n";

$listString = '# Mage.php Detected Version: '.$detectedVersion."\n\n";
if($detectedVersion != $version)
{
	$listString .= '## Checked Against Version: '.$version."\n\n";
}
$listString .= '## Changed Core Files'."\n".'========================================================'."\n* ";
$listString .= implode("\n* ",$modFiles);
$listString .= "\n\n".'## Altered Image Files'."\n".'========================================================'."\n* ";
$listString .= implode("\n* ",$modImages);
$listString .= "\n\n".'## Extra Files (Not present in core Magento)'."\n".'========================================================'."\n* ";
$listString .= implode("\n* ",$newFiles);
$listString .= "\n\n".'## Missing Files (Present in core Magento)'."\n".'========================================================'."\n* ";
$listString .= implode("\n* ",$missingFiles);

file_put_contents($listFile,$listString);
echo 'Report complete.  Building diff files.'."\n";

$writeString = '"<": Clean File'."\n".'">": Dirty File'."\n".'============================='."\n";

foreach($modFiles as $row)
{
	$writeString = '< Clean File'."\n".'> Dirty File'."\n".'============================='."\n";
	$writeString .= shell_exec('diff -r'. $use['opts'] .' '.$cleanDir.trim($row).' '.$dirtyDir.trim($row).' --strip-trailing-cr');
	
	$writeFile = $diffDir.'/'.str_replace('/','_',substr(trim($row),1)).'.diff';
	
	file_put_contents($writeFile,$writeString);
}
echo 'Diff file build complete.'."\n";