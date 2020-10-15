Starting to clear the cache ...
<br/>
<br/>
<?
    $cacheDir = getcwd().'/../local/cache/';
    $cacheSubDirs = scandir($cacheDir);
    if (!empty($cacheSubDirs)) {
        foreach ($cacheSubDirs as $cacheSubDir) {
            if (is_dir($cacheDir.$cacheSubDir)) {
                if ($cacheSubDir != '.' && $cacheSubDir != '..') {
                    print ('clearing /'.$cacheSubDir.' ...');
                    delTree($cacheDir.$cacheSubDir);
                    print ('done<br/>');
                }
            }
        }
    }
    
    function delTree($dir) { 
        $files = array_diff(scandir($dir), array('.','..')); 
        foreach ($files as $file) { 
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
        } 
        return rmdir($dir); 
    }
?>
<br/>
... done