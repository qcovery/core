<?php

$file = $argv[1];
if (strpos($file, '/') !== false) {
    list($workdir, ) = explode('/', $file, 2);
} else {
    $workdir = '.';
}

if (is_readable($file) && is_writable($file)) {
    $lines = file($file);
    foreach ($lines as $nr => $line) {
        if (preg_match('/^(use \*.+;)(.+)$/', $line, $matches)) {
            $modules = explode(' ', $matches[2]);
            foreach ($modules as $module) {
                if (is_dir($workdir . '/' . $module)) {
                    $lines[$nr] = preg_replace('/\*/', $module, $matches[1]);
                    break;
                } elseif ($module == 'none') {
                    unset($lines[$nr]);
                    break;
                }
            }
        } elseif (preg_match('/^(class|trait)/', $line)) {
            break;
        }
    }
    if ($fp = fopen($file, 'w')) {
        foreach ($lines as $line) {
            fwrite($fp, str_replace("\n", '', $line) . "\n");
        }
        fclose($fp);
    }
}

?>
