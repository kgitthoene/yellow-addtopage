<?php
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
  public $a_file = array();
  public $a_header_errors = array();
  public $meta_data_is_parsed = false;
  public $token = 'addtopage';

  private function __init() {
    $this->a_file = array();
    $this->a_header_errors = array();
    $this->meta_data_is_parsed = false;
  }

  private function __empty(string|null $str) {
    return $str === null || trim($str) === '';
  }

  private function __boolstr(string|null $bval) {
    return $bval ? 'true' : 'false';
  }

  private function __join($parts) {
    $rv = (is_string($parts)) ? $parts : join(DIRECTORY_SEPARATOR, $parts);
    return preg_replace('~' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . '+~', DIRECTORY_SEPARATOR, $rv);
  }

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

  private function __is_readable_file($fn) {
    return file_exists($fn) && is_readable($fn) && is_file($fn);
  }

  private function __get_file($fn, &$url) {
    $serverBase = $this->yellow->system->get("coreServerBase");
    $cwd = getcwd();
    $a_locations = array();
    if (!$this->__empty($this->yellow->system->get("coreDownloadLocation"))) {
      array_push($a_locations, $this->__join(array($cwd, $serverBase, $this->yellow->system->get("coreDownloadLocation"))));
    }
    foreach ($a_locations as $loc) {
      $filename = $this->__join(array($loc, $fn));
      $url = substr($filename, strlen($cwd));
      if ($this->__is_readable_file($filename)) {
        return $filename;
      }
    }
    return "";
  }

  private function __get_file_url($fn) {
    $url = "";
    $this->__get_file($fn, $url);
    return $url;
  }

  private function __get_theme_file() {
    $theme = $this->yellow->system->get("Theme");
    $coreServerBase = $this->yellow->system->get("coreServerBase");
    $theme_path = $this->__join(array($coreServerBase, 'system', 'themes'));
    $bn = "{$theme}.{$this->token}";
    $fn = $this->__join(array($theme_path, $bn));
    return $fn;
  }

  private function __get_update_installed() {
    $data = [];
    $serverBase = $this->yellow->system->get("coreServerBase");
    $cwd = getcwd();
    $fn = $this->__join(array($cwd, $serverBase, 'system', 'extensions', $this->yellow->system->get("UpdateInstalledFile")));
    $extn = null;
    if ($this->__is_readable_file($fn)) {
      $lines = file($fn, FILE_SKIP_EMPTY_LINES);
      foreach ($lines as $line) {
        if (preg_match('/^([^\s]+\s*):(.*)$/', trim($line), $matches)) {
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

  private function __insert_file_or_meta_data($page, $b_inline, $b_debug, $type, $file_location, $file_url) {
    $output = "";
    switch ($type) {
      case "CSS":
      case "JS":
        if ($b_inline) {
          # Read content of file.
          $content = file_get_contents($file_location);
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
              $output .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$file_url}\" />\n";
              break;
            case "JS":
              $output .= "<script type=\"text/javascript\" src=\"{$file_url}\"></script>\n";
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
        $comment = ($b_debug ? "/*\n * Access this settings by (example):\n *   const Page = globalThis[Symbol.for('Yellow-Page')];\n *   console.log(\"PAGE=\" + Page.title);\n */\n" : '');
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $output .= "<script type=\"text/javascript\">\n{$comment}globalThis[Symbol[\"for\"]('Yellow-Page')] = {$json};\n</script>\n";
        break;
      case "SYSTEM":
        $data = [];
        foreach ($this->yellow->system->getSettings() as $key => $value) {
          $key = strtolower((string) $key);
          $data[$key] = $value;
        }
        $comment = ($b_debug ? "/*\n * Access this settings by (example):\n *   const System = globalThis[Symbol.for('Yellow-System')];\n *   console.log(\"SITENAME=\" + System.sitename);\n */\n" : '');
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $output .= "<script type=\"text/javascript\">\n{$comment}globalThis[Symbol[\"for\"]('Yellow-System')] = {$json};\n</script>\n";
        break;
      case "EXTENSIONS":
        $data = [];
        $ext_data = $this->__get_update_installed();
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
        $comment = ($b_debug ? "/*\n * Access this settings by (example):\n *   const System = globalThis[Symbol.for('Yellow-Extensions')];\n *   console.log(\"SITENAME=\" + \"System.sitename\");\n */\n" : '');
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
        $output .= "<script type=\"text/javascript\">\n{$comment}globalThis[Symbol[\"for\"]('Yellow-Extensions')] = {$json};\n</script>\n";
        break;
    }
    return $output;
  }

  private function __format_page_header_errmsg($origin, $original_key, $partial, $msg) {
    if ($origin == 'THEME') {
      $urlfn = $this->__get_theme_file();
      return '<div><code>In the theme file for AddToPage, you defined: </code><code style="background-color:black;color:white;">&nbsp;' . $partial . '&nbsp;</code><code style="color:#F70D1A;background-color:white;"> ' . $msg . '</code> <code>THEME-FILE=\'[ROOT]' . $urlfn . '\'</code></div>';
    } else {
      return '<div><code>In the page header, you defined: </code><code style="background-color:black;color:white;">&nbsp;' . $original_key . ': ' . $partial . '&nbsp;</code><code style="color:#F70D1A;background-color:white;"> ' . $msg . '</code></div>';
    }
  }

  private function __format_content_errmsg($original_key, $partial, $msg) {
    return '<code style="background-color:black;color:white;padding-left:0;padding-right:0;border-radius:0;display:inline;">&nbsp;[' . $original_key . ': ' . $partial . ']&nbsp;</code><code style="color:#F70D1A;background-color:white;"> ' . $msg . '</code>';
  }

  private function __parse_text_and_array_push($original_key, $is_in_page_meta_data, $text, &$a_file, &$a_header_errors) {
    #----------
    # In the header you may add the following tokes:
    #   ---
    #   addtopage: <TYPE> <FILE-NAME> <OPTIONS>
    #   ---
    #
    # In the content you may use the following shortcode:
    #   [addtopage <TYPE> <FILE-NAME> <OPTIONS>]
    #
    # In the header you may aggregate multiple files separated by the pipe sign, '|'.
    # Example:
    #   ---
    #   addtopage: JS /js/zepto/zepto.min.js | CSS /css/style.css
    #   ---
    #
    # TYPE := {js,javascript,css,style}
    #   js or javascript -- Add as Javascript content.
    #   css or style     -- Add as CSS style sheet.
    #
    # FILE-NAME := Any file-name with path. May start with slash, '/'.
    #   Files are searched in:
    #     CoreDownloadLocation := /media/downloads/
    #
    #   The CoreAssetLocation doesn't exists by default! You may create it.
    #
    # OPTIONS := {debug,inline,footer}
    #   debug  -- Enable debug comments to the page output.
    #   inline -- Read the file contents and put it to the page output directly.
    #   footer -- Add file reference or content to the page footer.
    #             Default is to add the file reference or content to the header.
    #----------
    $output = null;
    $origin = ($original_key == 'THEME') ? 'THEME' : ($is_in_page_meta_data ? 'META-DATA' : 'CONTENT');
    foreach (explode("|", $text) as $partial) {
      $partial = trim((string) $partial);
      # Remove comment
      $partial_no_comment = preg_replace("/(^|[^\\\\])#.*$/", '', $partial);
      #$this->__debug("IN:PARTIAL='{$partial}' OUT:PARTIAL-NO-COMMENT='{$partial_no_comment}'");
      $partial = trim($partial_no_comment);
      if (empty($partial))
        continue;
      #
      list($type, $file, $options) = $this->yellow->toolbox->getTextArguments($partial);
      #
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
            array_push($this->a_header_errors, $this->__format_page_header_errmsg($origin, $original_key, $partial, $msg));
          } else {
            $output .= $this->__format_content_errmsg($original_key, $partial, $msg);
          }
      }
      if ($type_has_error)
        continue;
      #
      # Get options
      $a_options = explode(":", $options);
      foreach ($a_options as $key => $value)
        $a_options[$key] = strtoupper($value);
      $b_debug = in_array("DEBUG", $a_options);
      $b_inline = in_array("INLINE", $a_options);
      $b_footer = in_array("FOOTER", $a_options);
      $s_inline = $this->__boolstr($b_inline);
      #
      switch ($type) {
        case "CSS":
        case "JS":
          # Handle files: CSS or JS.
          # Get file
          $file_location = $this->__get_file($file, $file_url);
          $file_exists = !$this->__empty($file_location);
          $s_file_exists = $this->__boolstr($file_exists);
          if ($file_exists) {
            #
            #----------
            # Search, if file already exists.
            $file_exists_in_list = false;
            $file_exists_in_list_origin = '';
            foreach ($this->a_file as $fi) {
              if (($fi['FILE'] == $file) && ($fi['INLINE'] == $b_inline) && ($fi['FOOTER'] == $b_footer)) {
                $file_exists_in_list_origin = $fi['ORIGIN'];
                $file_exists_in_list = true;
                break;
              }
            }
            #----------
            #
            if ($file_exists_in_list) {
              $this->yellow->toolbox->log('warn', "File already defined! FILE='{$file}' ORIGIN='{$origin}' | FROM-ORIGIN='{$file_exists_in_list_origin}'");
            } else {
              array_push($this->a_file, array("TYPE" => $type, "FILE" => $file, "DEBUG" => $b_debug, "INLINE" => $b_inline, "FOOTER" => $b_footer, "ORIGIN" => $origin, "PRINTED" => false));
              if ($b_debug) {
                $output .= "<!-- FROM-PAGE-{$origin}: VALUE='{$partial}' TYPE='{$type}' INLINE={$s_inline} FILE-EXISTS={$s_file_exists} URL='{$file_url}' -->\n";
              }
            }
          } else {
            $file_url = $this->__get_file_url($file);
            $msg = 'ERROR -- File doesn\'t exist! FILE=\'[ROOT]' . $file_url . '\'';
            if ($is_in_page_meta_data) {
              array_push($this->a_header_errors, $this->__format_page_header_errmsg($origin, $original_key, $partial, $msg));
            } else {
              $output .= $this->__format_content_errmsg($original_key, $partial, $msg);
            }
            if ($b_debug) {
              $file_url = $this->__get_file_url($file);
              $output .= "<!-- FROM-PAGE-{$origin}: VALUE='{$partial}' TYPE='{$type}' INLINE={$s_inline} FILE-EXISTS={$s_file_exists} URL='{$file_url}' -->\n";
            }
          }
          break;
        case "PAGE":
        case "SYSTEM":
        case "EXTENSIONS":
          # Handle inserting page / system data.
          array_push($this->a_file, array("TYPE" => $type, "FILE" => $file, "DEBUG" => $b_debug, "INLINE" => true, "FOOTER" => $b_footer, "ORIGIN" => $origin, "PRINTED" => false));
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
      $result = $this->__parse_text_and_array_push($name, false, $text, $this->a_file, $this->a_header_errors);
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
    if (!$this->meta_data_is_parsed) {
      #
      #----------
      # Lookup for addtopage theme settings. In file: system/themes/<THEME>.addtopage
      $cwd = getcwd();
      $urlfn = $this->__get_theme_file();
      $fn = $this->__join(array($cwd, $urlfn));
      if ($this->__is_readable_file($fn)) {
        $lines = file($fn, FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
          $this->__parse_text_and_array_push('THEME', true, $line, $this->a_file, $this->a_header_errors);
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
          $this->__parse_text_and_array_push($key, true, $text, $this->a_file, $this->a_header_errors);
          break;
        }
      }
      #----------
      #
      $this->meta_data_is_parsed = true;
    }
    #----------
    # Inject files.
    if (!empty($this->a_file)) {
      foreach ($this->a_file as $item) {
        $type = "";
        $file = "";
        $b_debug = false;
        $b_inline = false;
        $b_footer = false;
        $b_printed = false;
        $origin = "";
        foreach ($item as $key => $value) {
          $type = ($key == "TYPE") ? $value : $type;
          $file = ($key == "FILE") ? $value : $file;
          $b_debug = ($key == "DEBUG") ? boolval($value) : $b_debug;
          $b_inline = ($key == "INLINE") ? boolval($value) : $b_inline;
          $b_footer = ($key == "FOOTER") ? $b_footer = boolval($value) : $b_footer;
          $origin = ($key == "ORIGIN") ? $value : $origin;
          $b_printed = ($key == "PRINTED") ? boolval($value) : $b_printed;
        }
        if ((!is_null($file)) and (!is_null($type))
          and (!$b_printed)
          and ((($name == "header") and (!$b_footer))
          or (($name == "footer") and ($b_footer)))) {
          #
          # Mark as printed.
          $item['PRINTED'] = true;
          #
          $file_location = $this->__get_file($file, $file_url);
          if ($b_debug) {
            $s_file_exists = $this->__boolstr(!$this->__empty($file_location));
            $s_is_footer = $this->__boolstr($b_footer);
            $s_is_inline = $this->__boolstr($b_inline);
            $fcomment = ($this->__empty($file_location) ? '' : "URL='{$file_url}' FILE-EXISTS='{$s_file_exists}' ");
            $output .= "<!-- onParsePageExtra(PAGE-LOCATION='{$name}') -- TYPE='{$type}' {$fcomment}FOOTER='{$s_is_footer}' INLINE='{$s_is_inline}' ORIGIN='{$origin}' -->\n";
          }
          # Insert file accordingly to type and mode (reference, inline).
          $output .= $this->__insert_file_or_meta_data($page, $b_inline, $b_debug, $type, $file_location, $file_url);
        }
      }
    }
    #----------
    # Output errors from meta data in footer.
    if (($name == "footer") && (!empty($this->a_header_errors))) {
      foreach ($this->a_header_errors as $msg) {
        $output .= "<div>{$msg}</div>\n";
      }
    }
    #----------
    return $output;
  }

}  # class YellowAddToPage
