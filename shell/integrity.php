<?php 

require_once 'abstract.php';

class Mage_Shell_Integrity extends Mage_Shell_Abstract
{

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f integrity.php -- [options]

  --local <name>				List local classes for a given namespace, name.  This is in addition to the default checks for Mage, Enterprise, Varien and Zend.
  --output <file>				Save results of this scan to a file, based in the Magento root directory.  Root must be writeable.
  diff							Create diffs of local files found in _DIFFS
  silent						Hides the echo of the result for this scan.
  help                          Displays this usage text.

USAGE;
    }
	
	
    public function run()
    {
		$customDirectory = $this->getArg('local');
		$output = '';
		
		$output .= 'Magento Overrides Scanner'."\n";
		$output .= '============================='."\n\n";
		
		//List installed extensions and flag disabled ones
		$output .= 'Extensions Installed: '."\n";
		$extensionsInstalled = $this->_getExtensionList();
		if(empty($extensionsInstalled))
		{
			$output .= "\t".'(None)'."\n";
		}
		else
		{
			$output .= $extensionsInstalled;
		}
		
		//List rewritten classes
		$output .= 'Rewritten classes: '."\n";
		
		
		//Models
		$models = Mage::getConfig()->getNode('global/models');
		$output .= "\t".'Models:'."\n";
		$modelRewrites = $this->_showRewrites($models);
		if(empty($modelRewrites))
		{
			$output .= "\t\t".'(None)'."\n";
		}
		else
		{
			$output .= $modelRewrites;
		}
		
		//Blocks
		$blocks = Mage::getConfig()->getNode('global/blocks');
		$output .= "\t".'Blocks:'."\n";
		$blockRewrites = $this->_showRewrites($blocks);
		if(empty($blockRewrites))
		{
			$output .= "\t\t".'(None)'."\n";
		}
		else
		{
			$output .= $blockRewrites;
		}
		
		
		//Controllers
		//$output .= "\t".'Controllers:'."\n";
		//$output .= "\t\t".'(Coming Soon)'."\n";
		
		//List local Enterprise and Mage files
		$output .= 'Local classes: '."\n";
		$diff = $this->getArg('diff');
		$localFiles = $this->_readLocalFiles($customDirectory, $diff);
		if(empty($localFiles))
		{
			$output .= "\t".'(None)'."\n";
		}
		else
		{
			$output .= $localFiles;
			if($diff)
			{
				$output .= "Diffs created\n";
			}
		}
		
		if($this->getArg('output'))
		{
			file_put_contents($this->_getRootPath().$this->getArg('output'),$output);
			$output .= 'Saved results to "'.$this->_getRootPath().$this->getArg('output')."\"\n";
		}
		
		if(!$this->getArg('silent'))
		{
			echo $output;
		}
		
	}
	
	protected function _getExtensionList()
	{
		$modules = Mage::getConfig()->getNode('modules');
		$coreModules = array();
		$output = '';
	
		foreach($modules as $wrapper => $moduledata)
		{
			foreach($moduledata as $_moduleName => $data)
			{
				if($data->codePool == 'core')
				{
					continue;
				}
				
				$ts = ceil((32-strlen($_moduleName))/8);
				$ts = max(1,$ts);
				$output .= "\t".$_moduleName;
				while($ts-- > 0)
				{ 
					$output .= "\t"; 
				}
				$version = $data->version;
				if($data->active == 'false')
				{
					$version = 'disabled';
				}
				$output .= $version.'/'.$data->codePool."\n";
			}
		}
		
		return $output;
	}
	
	protected function _readLocalFiles($additional = null, $diff = false)
	{
		$result = '';
		$localPath = $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR;
		
		$dirs = array(
			'Mage',
			'Enterprise',
			'Varien',
			'Zend',
		);
		
		if($additional)
		{
			$dirs[] = $additional;
		}
		
		foreach($dirs as $_directory)
		{	
			$result .= implode("\n",$this->_processDirectory($localPath.$_directory, $diff))."\n";
		}
		
		if(!$result)
		{
			$result .= "\t".'(None)'."\n";
		}
		
		return $result;
	}
	
	protected function _processDirectory($dir, $diff = false) {
		$localPath = $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR;
		$corePath = $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR;
		
		if (is_dir($dir)) {
			for ($list = array(),$handle = opendir($dir); (FALSE !== ($file = readdir($handle)));) {
				if (($file != '.' && $file != '..') && (file_exists($path = $dir.'/'.$file))) {
					if (is_dir($path)) {
						$list = array_merge($list, $this->_processDirectory($path,$diff));
					} else {
						do if (!is_dir($path)) {
							//File
							$entry = "\t";
							$filePath = str_replace($localPath,'',$path);
							$fixedPath = str_replace(array($localPath,'.php'),'',$path);
							$fixedPath = str_replace(array('/','\\'),'_',$fixedPath);
							$entry .= $fixedPath;

							if($diff)
							{
								$writeString = '< Clean File'."\n".'> Dirty File'."\n".'============================='."\n";
								$writeString .= shell_exec('diff -bBE '.$corePath.$filePath.' '.$localPath.$filePath.' --strip-trailing-cr');
								
								$writeFile = '_DIFFS'.DS.str_replace('/','_',trim($fixedPath)).'.diff';
								
								file_put_contents($writeFile,$writeString);
							}
							break;
						} else {
							//Directory
							break;
						} while (FALSE);
						$list[] = $entry;
					}
				}
			}
			closedir($handle);
			return $list;
		} else return FALSE;
	}
	
	protected function _showRewrites($nodeHead)
	{
		$rewrites = '';
	
		foreach($nodeHead as $wrapper => $moduledata)
		{
			foreach($moduledata as $_shortname => $module)
			{
				foreach($module as $key => $data)
				{
					if($key != 'rewrite')
					{
						continue;
					}
					
					foreach($data as $_accessor => $class)
					{
						$rewrites .= "\t\t";
					
						$rewrites .= $_shortname.'/'.$_accessor.' => '.$class;
					
						$rewrites .= "\n";
					}
				}
			}
		}
	
		if(!$rewrites)
		{
			$rewrites .= "\t\t(None)\n";
		}
	
		return $rewrites;
	}
}

$shell = new Mage_Shell_Integrity();
$shell->run();