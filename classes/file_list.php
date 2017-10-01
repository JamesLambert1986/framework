<?php
class file_list
{

	public function __construct ($root, $site_root, $folder, $sub_folders) {

		$this->scan_dir($root,$folder, $sub_folders);
		$this->scan_dir($site_root,$folder, $sub_folders);
	}

	private function scan_dir($root, $folder, $sub_folders){
		
		foreach($sub_folders as $index => $sub_folder){
			
			$directory = $root."/".$folder."/".$sub_folder;
			if(file_exists($directory)){

				$scanned_directory = array_diff(scandir($directory), array('..', '.'));

				foreach($scanned_directory as $file) {
					
					$dotpos = strrpos($file, ".");
					
					if($dotpos > 0)
					{
						$file_name = substr($file,0,strrpos($file, "."));
						$this->{$sub_folder}[$file_name] = $directory."/".$file;
					}
				}
			}
		}
    }
}
?>