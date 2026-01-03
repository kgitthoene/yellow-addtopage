<?php
// AddToPage extension, https://github.com/kgitthoene/yellow-addtopage
#----------
# MIT License
#
# Copyright (c) 2025 Kai Thoene
#
# Permission is hereby granted, free of charge, to any person
# obtaining a copy of this software and associated
# documentation files (the "Software"), to deal in the Software
# without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense,
# and/or sell copies of the Software, and to permit persons to
# whom the Software is furnished to do so, subject to the
# following conditions:
#
# The above copyright notice and this permission notice shall
# be included in all copies or substantial portions of the
# Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
# KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
# WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
# PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
# COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
# OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
# SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
#----------
class YellowAddToPage {
    const VERSION = "0.4.0";
    public $yellow;            //access to API
    public $debug = true;
    public $aInstructions = array();
    public $aHeaderThemeErrors = array();
    public $bIsMetaDataIsParsed = false;
    public $token = "addtopage";

    // Initialize intance data
    private function __init() {
        $this->aInstructions = array();
        $this->aHeaderThemeErrors = array();
        $this->bIsMetaDataIsParsed = false;
    }

    // Test against null or empty strings or strings filled with whitespaces
    private function __empty(string|null $str) {
        return $str === null || trim($str) === "";
    }

    // Converts boolean value to string
    private function __boolstr(string|null $bVal) {
        return $bVal ? "true" : "false";
    }

    // Joins path components from string or array
    private function __join(string|array $parts) {
        $rv = (is_string($parts)) ? $parts : join(DIRECTORY_SEPARATOR, $parts);
        return preg_replace('~' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . '+~', DIRECTORY_SEPARATOR, $rv);
    }

    // Put message to systems log file, if in debug mode
    private function __debug(string|array $msg) {
        if ($this->debug) {
            if (is_array($msg)) {
                foreach ($msg as $m) {
                    $this->__debug($m);
                }
            } else {
                $this->yellow->toolbox->log('debug', (string) $msg);
            }
        }
    }

    // Tests if a file exists, is readable and is a regular file
    private function __isReadableFile($fn) {
        return file_exists($fn) && is_readable($fn) && is_file($fn);
    }

    // Get real path for a file inside the <coreDownloadLocation>, calculates also the files URL
    private function __getFile($fn, &$url) {
        $serverBase = $this->yellow->system->get("coreServerBase");
        $cwd = getcwd();
        $aLoc = array();
        if (!$this->__empty($this->yellow->system->get("coreDownloadLocation"))) {
            array_push($aLoc, $this->__join(array($cwd, $serverBase, $this->yellow->system->get("coreDownloadLocation"))));
        }
        foreach ($aLoc as $loc) {
            $filename = $this->__join(array($loc, $fn));
            $url = substr($filename, strlen($cwd));
            if ($this->__isReadableFile($filename)) {
                return $filename;
            }
        }
        return "";
    }

    // Get the URL for a file inside the <coreDownloadLocation>, even if the file not exits
    private function __getFileURL($fn) {
        $url = "";
        $this->__getFile($fn, $url);
        return $url;
    }

    // Get the URL for themes .addtopage file
    private function __getThemeFileURL() {
        $theme = $this->yellow->system->get("Theme");
        $coreServerBase = $this->yellow->system->get("coreServerBase");
        $themePath = $this->__join(array($coreServerBase, 'system', 'themes'));
        $bn = "{$theme}.{$this->token}";
        $fn = $this->__join(array($themePath, $bn));
        return $fn;
    }

    // Convert data from system/extensions/update-installed.ini to a PHP data structure
    private function __getUpdateInstalled() {
        $data = [];
        $serverBase = $this->yellow->system->get("coreServerBase");
        $cwd = getcwd();
        $fn = $this->__join(array($cwd, $serverBase, 'system', 'extensions', $this->yellow->system->get("UpdateInstalledFile")));
        $extn = null;
        if ($this->__isReadableFile($fn)) {
            $lines = file($fn, FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (preg_match('/^\s*([^\s]+\s*):(.*)$/', trim($line), $matches)) {
                    $key = trim($matches[1]);
                    $value = trim($matches[2]);
                    if (strtolower($key) == 'extension') {
                        # New extension information starts
                        $extn = $value;
                        $data[$extn] = [];
                        $data[$extn][$key] = $value;
                    } elseif (!is_null($extn)) {
                        $data[$extn][$key] = $value;
                    }
                }
            }
        }
        return $data;
    }

    // Insert link to JS or CSS file, insert the content of a JS or CSS file (inline) or insert meta data structures as JS
    private function __insertFileOrMetaData($page, $bIsInline, $bIsDebug, $type, $fileLoc, $fileURL) {
        $output = "";
        switch ($type) {
            case "CSS":
            case "JS":
                if ($bIsInline) {
                    # Read content of file.
                    $content = file_get_contents($fileLoc);
                    $content .= ((mb_substr($content, -1) == "\n") ? "" : "\n");
                    switch ($type) {
                        case "CSS":
                            $output .= "<style>\n" . $content . "</style>\n";
                            break;
                        case "JS":
                            $output .= "<script>\n" . $content . "</script>\n";
                            break;
                    }
                } else {
                    switch ($type) {
                        case "CSS":
                            $output .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$fileURL}\" />\n";
                            break;
                        case "JS":
                            $output .= "<script type=\"text/javascript\" src=\"{$fileURL}\"></script>\n";
                            break;
                    }
                }
                break;
            case "PAGE":
                $data = [];
                foreach ($page->metaData as $key => $value) {
                    $key = strtolower((string) $key);
                    $data[$key] = $value;
                }
                $comment = ($bIsDebug ? "/*\n * Access this settings by (example):\n *   const Page = globalThis[Symbol.for('Yellow-Page')];\n *   console.log(\"PAGE=\" + Page.title);\n */\n" : "");
                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $output .= "<script type=\"text/javascript\">\n{$comment}globalThis[Symbol[\"for\"]('Yellow-Page')] = {$json};\n</script>\n";
                break;
            case "SYSTEM":
                $data = [];
                foreach ($this->yellow->system->getSettings() as $key => $value) {
                    $key = strtolower((string) $key);
                    $data[$key] = $value;
                }
                $comment = ($bIsDebug ? "/*\n * Access this settings by (example):\n *   const System = globalThis[Symbol.for('Yellow-System')];\n *   console.log(\"SITENAME=\" + System.sitename);\n */\n" : "");
                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $output .= "<script type=\"text/javascript\">\n{$comment}globalThis[Symbol[\"for\"]('Yellow-System')] = {$json};\n</script>\n";
                break;
            case "EXTENSIONS":
                $data = [];
                $ext_data = $this->__getUpdateInstalled();
                foreach ($this->yellow->extension->data as $key => $value) {
                    $extvalue = [];
                    foreach ($ext_data as $ekey => $evalue) {
                        if(strtolower($key) == strtolower($ekey)) {
                            $extvalue = $evalue;
                            break;
                        }
                    }
                    $data[$key] = ["class" => get_class($this->yellow->extension->get($key))];
                    if(!!$extvalue) {
                        $data[$key]['update-installed'] = $extvalue;
                    }
                }
                $comment = ($bIsDebug ? "/*\n * Access this settings by (example):\n *   const Extensions = globalThis[Symbol.for('Yellow-Extensions')];\n *   for (var prop in Extensions) {\n *     if (Object.prototype.hasOwnProperty.call(Extensions, prop)) {\n *       console.log(\"EXTENSION-NAME=\" + prop);\n *       console.log(\"  CLASS=\" + Extensions[prop][\"class\"]);\n *     }\n *   }\n */\n" : "");
                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
                $output .= "<script type=\"text/javascript\">\n{$comment}globalThis[Symbol[\"for\"]('Yellow-Extensions')] = {$json};\n</script>\n";
                break;
        }
        return $output;
    }

    // Format HTML error message for errors occured in page setiings or theme instructions file
    private function __formatPageSettingsOrThemeInstructionsError($origin, $original_key, $partial, $msg) {
        if ($origin == "THEME") {
            $urlfn = $this->__getThemeFileURL();
            return '<div><code>In the theme file for AddToPage, you defined: </code><code style="background-color:black;color:white;">&nbsp;' . $partial . '&nbsp;</code><code style="color:#F70D1A;background-color:white;"> ' . $msg . '</code> <code>THEME-FILE=\'[ROOT]' . $urlfn . '\'</code></div>';
        } else {
            return '<div><code>In the page header, you defined: </code><code style="background-color:black;color:white;">&nbsp;' . $original_key . ': ' . $partial . '&nbsp;</code><code style="color:#F70D1A;background-color:white;"> ' . $msg . '</code></div>';
        }
    }

    // Format HTML error message for errors occured in shortcuts
    private function __formatShortcutError($original_key, $partial, $msg) {
        return '<code style="background-color:black;color:white;padding-left:0;padding-right:0;border-radius:0;display:inline;">&nbsp;[' . $original_key . ': ' . $partial . ']&nbsp;</code><code style="color:#F70D1A;background-color:white;"> ' . $msg . '</code>';
    }

    // Parse AddToPage instructions and add it to the output array
    private function __parseInstruction($original_key, $is_in_page_meta_data, $text, &$aInstructions, &$aHeaderThemeErrors) {
        $output = null;
        $origin = ($original_key == "THEME") ? "THEME" : ($is_in_page_meta_data ? "META-DATA" : "CONTENT");
        foreach (explode("|", $text) as $partial) {
            $partial = trim((string) $partial);
            $partial_no_comment = preg_replace("/(^|[^\\\\])#.*$/", "", $partial);
            $partial = trim($partial_no_comment);
            if (empty($partial))
                continue;
            list($type, $file, $options) = $this->yellow->toolbox->getTextArguments($partial);
            $type = strtoupper($type);
            $type_has_error = false;
            switch ($type) {
                case "CSS":
                case "STYLE":
                    $type = "CSS";
                    break;
                case "JAVASCRIPT":
                case "JS":
                    $type = "JS";
                    break;
                case "PAGE-JSON":
                case "PAGE":
                    $type = "PAGE";
                    break;
                case "SYSTEM-JSON":
                case "SYSTEM":
                    $type = "SYSTEM";
                    break;
                case "EXTENSIONS-JSON":
                case "EXTENSIONS":
                    $type = "EXTENSIONS";
                    break;
                default:
                    $type_has_error = true;
                    $msg = "ERROR -- Invalid or no type! Use: SYSTEM, PAGE, EXTENSIONS, JS or CSS!";
                    if ($is_in_page_meta_data) {
                        array_push($this->aHeaderThemeErrors, $this->__formatPageSettingsOrThemeInstructionsError($origin, $original_key, $partial, $msg));
                    } else {
                        $output .= $this->__formatShortcutError($original_key, $partial, $msg);
                    }
            }
            if ($type_has_error)
                continue;
            $a_options = explode(":", $options);
            foreach ($a_options as $key => $value)
                $a_options[$key] = strtoupper($value);
            $bIsDebug = in_array("DEBUG", $a_options);
            $bIsInline = in_array("INLINE", $a_options);
            $bIsFooter = in_array("FOOTER", $a_options);
            $s_inline = $this->__boolstr($bIsInline);
            switch ($type) {
                case "CSS":
                case "JS":
                    $fileLoc = $this->__getFile($file, $fileURL);
                    $bFileExists = !$this->__empty($fileLoc);
                    $sFileExists = $this->__boolstr($bFileExists);
                    if ($bFileExists) {
                        $bFileExitsInList = false;
                        $sFileOrigin = "";
                        foreach ($this->aInstructions as $fi) {
                            if (($fi["FILE"] == $file) && ($fi["INLINE"] == $bIsInline) && ($fi["FOOTER"] == $bIsFooter)) {
                                $sFileOrigin = $fi["ORIGIN"];
                                $bFileExitsInList = true;
                                break;
                            }
                        }
                        #----------
                        #
                        if ($bFileExitsInList) {
                            $this->yellow->toolbox->log("warn", "File already defined! FILE='{$file}' ORIGIN='{$origin}' | FROM-ORIGIN='{$sFileOrigin}'");
                        } else {
                            array_push($this->aInstructions, array("TYPE" => $type, "FILE" => $file, "DEBUG" => $bIsDebug, "INLINE" => $bIsInline, "FOOTER" => $bIsFooter, "ORIGIN" => $origin, "PRINTED" => false));
                            if ($bIsDebug) {
                                $output .= "<!-- FROM-PAGE-{$origin}: VALUE='{$partial}' TYPE='{$type}' INLINE={$s_inline} FILE-EXISTS={$sFileExists} URL='{$fileURL}' -->\n";
                            }
                        }
                    } else {
                        $fileURL = $this->__getFileURL($file);
                        $msg = "ERROR -- File doesn't exist! FILE='[ROOT]" . $fileURL . "'";
                        if ($is_in_page_meta_data) {
                            array_push($this->aHeaderThemeErrors, $this->__formatPageSettingsOrThemeInstructionsError($origin, $original_key, $partial, $msg));
                        } else {
                            $output .= $this->__formatShortcutError($original_key, $partial, $msg);
                        }
                        if ($bIsDebug) {
                            $fileURL = $this->__getFileURL($file);
                            $output .= "<!-- FROM-PAGE-{$origin}: VALUE='{$partial}' TYPE='{$type}' INLINE={$s_inline} FILE-EXISTS={$sFileExists} URL='{$fileURL}' -->\n";
                        }
                    }
                    break;
                case "PAGE":
                case "SYSTEM":
                case "EXTENSIONS":
                    # Handle inserting page / system data.
                    array_push($this->aInstructions, array("TYPE" => $type, "FILE" => $file, "DEBUG" => $bIsDebug, "INLINE" => true, "FOOTER" => $bIsFooter, "ORIGIN" => $origin, "PRINTED" => false));
                    break;
            }
        }
        return $output;
    }

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("AddToPageDebugMode", ($this->debug ? 1 : 0));
        $addtopagedebugmode = $this->yellow->system->get("AddToPageDebugMode");
        $this->debug = ($addtopagedebugmode != 0);
        $this->__init();
    }

    // Handle page content element
    public function onParseContentElement($page, $name, $text, $attributes, $type) {
        $output = null;
        if ((strtolower($name) == $this->token) && ($type == "block" || $type == "inline")) {
            $result = $this->__parseInstruction($name, false, $text, $this->aInstructions, $this->aHeaderThemeErrors);
            if (!is_null($result)) {
                $output .= $result;
            }
        }
        return $output;
    }

    // Handle page extra data
    public function onParsePageExtra($page, $name) {
        $output = null;
        #----------
        # Parse meta data / i.e. page header.
        if (!$this->bIsMetaDataIsParsed) {
            #
            #----------
            # Lookup for addtopage theme settings. In file: system/themes/<THEME>.addtopage
            $cwd = getcwd();
            $urlfn = $this->__getThemeFileURL();
            $fn = $this->__join(array($cwd, $urlfn));
            if ($this->__isReadableFile($fn)) {
                $lines = file($fn, FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $this->__parseInstruction("THEME", true, $line, $this->aInstructions, $this->aHeaderThemeErrors);
                }
            }
            #----------
            #
            #----------
            # Parse page meta data.
            foreach ($page->metaData as $key => $text) {
                $key = strtolower((string) $key);
                $text = (string) $text;
                if ($key == $this->token) {
                    $this->__parseInstruction($key, true, $text, $this->aInstructions, $this->aHeaderThemeErrors);
                    break;
                }
            }
            #----------
            #
            $this->bIsMetaDataIsParsed = true;
        }
        #----------
        # Inject files.
        if (!empty($this->aInstructions)) {
            foreach ($this->aInstructions as $item) {
                $type = "";
                $file = "";
                $bIsDebug = false;
                $bIsInline = false;
                $bIsFooter = false;
                $bIsPrinted = false;
                $origin = "";
                foreach ($item as $key => $value) {
                    $type = ($key == "TYPE") ? $value : $type;
                    $file = ($key == "FILE") ? $value : $file;
                    $bIsDebug = ($key == "DEBUG") ? boolval($value) : $bIsDebug;
                    $bIsInline = ($key == "INLINE") ? boolval($value) : $bIsInline;
                    $bIsFooter = ($key == "FOOTER") ? $bIsFooter = boolval($value) : $bIsFooter;
                    $origin = ($key == "ORIGIN") ? $value : $origin;
                    $bIsPrinted = ($key == "PRINTED") ? boolval($value) : $bIsPrinted;
                }
                if ((!is_null($file)) and (!is_null($type))
                    and (!$bIsPrinted)
                    and ((($name == "header") and (!$bIsFooter))
                    or (($name == "footer") and ($bIsFooter)))) {
                    $item['PRINTED'] = true;
                    $fileLoc = $this->__getFile($file, $fileURL);
                    if ($bIsDebug) {
                        $sFileExists = $this->__boolstr(!$this->__empty($fileLoc));
                        $sIsFooter = $this->__boolstr($bIsFooter);
                        $sIsInline = $this->__boolstr($bIsInline);
                        $comment = ($this->__empty($fileLoc) ? "" : "URL='{$fileURL}' FILE-EXISTS='{$sFileExists}' ");
                        $output .= "<!-- onParsePageExtra(PAGE-LOCATION='{$name}') -- TYPE='{$type}' {$comment}FOOTER='{$sIsFooter}' INLINE='{$sIsInline}' ORIGIN='{$origin}' -->\n";
                    }
                    $output .= $this->__insertFileOrMetaData($page, $bIsInline, $bIsDebug, $type, $fileLoc, $fileURL);
                }
            }
        }
        if (($name == "footer") && (!empty($this->aHeaderThemeErrors))) {
            foreach ($this->aHeaderThemeErrors as $msg) {
                $output .= "<div>{$msg}</div>\n";
            }
        }
        return $output;
    }

}  # class YellowAddToPage
