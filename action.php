<?php
/**
 * Action Plugin ArchiveUpload
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Myron Turner <turnermm02@shaw.ca>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');


class action_plugin_strreplace extends DokuWiki_Action_Plugin {
     private $do_replace = false;
     private $metafilename = 'strreplace:searched';
     private $metafilepath;
     private $id;

     /**
      * Registers our callback functions
      */
    function register(&$controller) { 
       $controller->register_hook('IO_WIKIPAGE_READ', 'AFTER', $this, 'substitutions');      
       $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, '_ini');    
       $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'write_metafile');    
    }
   
    function __construct() {
          $this->metafilepath = metaFN($this->metafilename, '.ser');
    }
    
    function _ini(&$event, $param) {
        global $ACT,$INFO;
        $this->id = $INFO['id'];                           
        
        $this->do_replace = $this->getConf('do_replace');              
        if(!$this->do_replace) {
            if(file_exists($this->metafilepath)) {
             unlink($this->metafilepath);              
           }
            return; 
        }
        
        if($ACT != 'edit') {
             return;
        }

        $searched =$this->get_metadata();
        if(in_array($this->id,$searched) && !array_key_exists ('_s' ,$searched)) {
            $this->do_replace = false;
        }         
    }     

 
    function substitutions(&$event, $param) {   
     global $ACT;   
        if($ACT != 'edit') return;
        
            if(!$this->do_replace) return;      
            if($event->data[1]) {
               $doc = $event->data[1] . ':' . $event->data[2];
            }
            else {
                $doc = $event->data[2];
            }
       
            if( $doc!= $this->id) return;  // prevents processing of pages loaded by template, e.g. sidebar
              $count = 0;
              for($i=1; $i< 5; $i++) {
                  $s_term = 'search_' . $i;   
                  $r_term = 'replace_' . $i;   
                  $srch = $this->getConf($s_term);
                  $srch = trim($srch);
                  if($srch) {
                     $srch = '#'. preg_quote($srch) .'#ms';                     
                     $repl = $this->getConf($r_term);                                          
                    $event->result = preg_replace($srch,$repl,$event->result, -1, $_count);  
                    $count += $_count;
                 }   
            }  
             
            $searched = $this->get_metadata();
            
            if($count) {
              $searched['_s'] = $this->id; 
            }
            elseif(in_array($this->id,$searched)) {
               return;
            }  
            else $searched[] = $this->id; 
            io_saveFile($this->metafilepath,serialize($searched));                
     }   

    function write_metafile(&$event, $param) {
       global $ACT;
       
       if(!is_array($ACT)) return;
       if(!$ACT['save']) return;
       
        $searched = $this->get_metadata();
    
        if( array_key_exists ('_s',$searched)  && $searched['_s'] == $this->id) {
           unset($searched['_s']);       
        }
        else  return;       
         
         if(in_array($this->id,$searched)) return;
        $searched[] = $this->id;   
    
        io_saveFile($this->metafilepath,serialize($searched));      
    }    
    
    function get_metadata() {
        $searched = unserialize(io_readFile($this->metafilepath,false)); 
        if(!$searched) return array();
        return $searched;
    }   

   
}

