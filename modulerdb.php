<?php
/*

ModulerDB 1.01. Convenient operation and access to MySQL database.
Copyright (C) 2012 TrigenSoftware

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Methods:
    $db = new ModulerDB(array(
        'host' => 'localhost',
        'username' => 'user_name',
        'password' => '1234',
        'dbname' => 'my_db'
    ));

    array $db->tables() - list of tables;
          ['table1','table2'...]
  
    bool $db->tableisexist('table1') - true if table is exist;

    string $db->es('"str"') - escape string;
          '\\"str\\"'
  
    array $db->q('select * from table1') - mysql query; 
          [
            { 'username' : 'name', 'password' : '1234' },
            { 'username' : 'namu', 'password' : '1234' }
          ]
    

    object $db['table1'] - methods of working with tables;
    
    array $db['table1']->columns() - return info about columns;
          [
            { 'Field' : 'username', 'Type' : 'varchar(50)', 'Nulle' : 'NO', 'Key' : '', 'Default' : '', 'Extra' : '' },
            { 'Field' : 'password', 'Type' : 'varchar(50)', 'Nulle' : 'NO', 'Key' : '', 'Default' : '', 'Extra' : '' }
          ]
    
    string $db['table1']->type('username') - return type of column;
           varchar(50)
    
    void $db['table1']->model(array( - model of select result
        'user' => '@username',
        'data' => '@table2(user=@username).first' //last/merge
    ))
    
    array $db['table1']->select('username="namu" or username="name"') - select data from db in model form 
                         select('username=','namu','or username=','name')
                         select(array('username=','namu','or username=','name'))
          [
            { 'user' : 'name', 'data' : { 'bday' : '10.01.1992' } },
            { 'user' : 'namu', 'data' : { 'bday' : '10.02.1992' } }
          ] 
                custom model:
                         select('username="namu" or username="name"',arrayModel) 
                         select('username=','namu','or username=','name',arrayModel)
                         select(array('username=','namu','or username=','name'),arrayModel)
 
    bool $db['table199']->create('id int, data varchar(100)') - creates new table with name 'table199';
                        ->create(array('id' => 'int', 'data' => 'varchar(100)'))

    bool $db['table1']->add(array('email' => 'varchar(50)')) - add a columns;
    bool $db['table1']->addAfter(array('email' => 'varchar(50)'),'username') - add a columns after another column;
    bool $db['table1']->addFirst(array('email' => 'varchar(50)')) - add a columns before first;
    bool $db['table1']->insert(array('user'=>'newuser','email'=>'some@mail.pro')) - insert new row;
    
    bool $db['table199']->rename('table3') - rename table;
    bool $db['table1']->change('email','mail') - change column;
                        change('email','mail','varchar(120)')
    bool $db['table1']->first('mail') - make columns first;
                        first(array('mail'))
    bool $db['table1']->after('mail','username') - move columns after column;
                        after(array('mail'),'username')
    bool $db['table1']->update('username="namu" or username="name"',array('mail' => 'null')) - update data;
                        update('username=','namu','or username=','name',array('mail' => 'null'))
                        update(array('username=','namu','or username=','name'),array('mail' => 'null'))

    bool $db['table1']->drop() - delete table;
                        drop('mail') - delete column;
                        drop(array('mail','username')) - delete columns;
    bool $db['table1']->delete('username="namu" or username="name"') - delete row;
                        delete('username=','namu','or username=','name')
                        delete(array('username=','namu','or username=','name')
*/   
  
 class ModulerDB extends ArrayObject {
    public  $error,
            $ok = true;
            
    private $tables = array(),
            $tdm = array(),
            $dbconf = array(),
            $con;
    
    function __construct($dbconf){
        if(!$this->dbconnect($dbconf)) return;
        $this->dbconf = $dbconf;
    }
    
    function offsetGet($t){ 
        $this->tables = array();
        $ts = $this->q('show tables');
        foreach($ts as $value){
           if(!isset($this->tdm[current($value)])) $this->tdm[current($value)] = array();
           $this->tables[current($value)] = new mdbTable(current($value),&$this->tables,&$this->tdm,$this->dbconf);
        }
        if($this->tableisexist($t)) return $this->tables[$t];
        else return new mdbTable($t,&$this->tables,&$this->tdm,$this->dbconf);
    }
    
    function tables(){
        $ts = array();
        foreach($this->tables as $key => $value) array_push($ts,$key);
        return $ts;
    }    
    
    function tableisexist($t){
        $ts = $this->q('show tables');
        foreach($ts as $value) if(current($value) == $t) return true;
        return false;
    }
    
    private function dbconnect($dbconf){
        $this->con = mysql_connect($dbconf['host'],$dbconf['username'],$dbconf['password']);
        if(!$this->con){
           $this->error = 'could not connect: '.mysql_error();
           $this->ok = false;
           return false;
        }
    
        if(!mysql_select_db($dbconf['dbname'],$this->con)){
           $this->error = 'cant use '.$dbconf['dbname'].': '.mysql_error();
           $this->ok = false;
           return false;
        }
         
        return true;
    }
    
    function es($str){  //escape string 
        return mysql_real_escape_string($str); 
    }
     
    function q($query){ //query
        $ret = array();
        $q = mysql_query($query,$this->con);
        while($row = mysql_fetch_assoc($q)) array_push($ret, $row);
         
        return $ret;
    }

 }
 
 class mdbTable {
    private $table,
            $tables,
            $tdm,
            $dbconf = array(),
            $con;
 
    function __construct($table, $tables, $tdm, $dbconf){ 
        if(!$this->dbconnect($dbconf)) return;
        $this->table = $table;
        $this->tables = &$tables;
        $this->tdm = &$tdm;
        $this->dbconf = $dbconf;
    }
    
    function create($cols){     
        if($this->tableisexist($this->table)) return false;
        if(is_array($cols)){
           $tcols = $cols;
           $cols = '';
           foreach($tcols as $vname => $vtype){
              $cols .= $vname." ".$vtype.", ";
           }
           $cols = substr($cols,0,strlen($cols)-2);
        }      
        $this->dbq("create table ".$this->table." (".$cols.")");
        $this->tables[$this->table] = new mdbTable($this->table,&$this->tables,$this->dbconf);
        
        return $this->tableisexist($this->table);
    }
    
    function drop(){
        $arguments = func_get_args();
        if(!$this->tableisexist($this->table)) return false;    
        
        if(count($arguments) == 0){    
          $this->dbq("drop table ".$this->table);
          return !$this->tableisexist($this->table);
        } else if(!is_array($arguments[0])){
          if(!$this->colisexist($arguments[0])) return false;    
          $this->dbq("alter table ".$this->table." drop ".$arguments[0]);
          return !$this->colisexist($arguments[0]);
        } else if(is_array($arguments[0])){
          $ret = true;
          
          foreach($arguments[0] as $value)
             if($this->colisexist($value)) $this->dbq("alter table ".$this->table." drop ".$value);
             else $ret = false;
             
          return $ret;
        }
    }
    
    function rename($tn){
        if(!$this->tableisexist($this->table) || $this->tableisexist($tn)) return false;
        
        $this->dbq("rename table ".$this->table." to ".$tn);
        
        return (!$this->tableisexist($this->table) && $this->tableisexist($tn));
    }
    
    function columns(){       
        if(!$this->tableisexist($this->table)) return false;
        
        return $this->dbq("show columns from ".$this->dbes($this->table));
    }
    
    function model($m){
        if(!$this->tableisexist($this->table)) return false;
        
        $this->tdm[$this->table] = $m;
    }
    
    function select(){             
        if(!$this->tableisexist($this->table)) return false;  
        
        $arguments = func_get_args();
        $where = "";
        $selected = array();
        $model = $this->tdm[$this->table];
        
        if(count($arguments) > 1 && is_array($arguments[count($arguments)-1])){
           $model = $arguments[count($arguments)-1];
           unset($arguments[count($arguments)-1]);
        }
        if(count($arguments) == 1 && is_array($arguments[0])) $arguments = $arguments[0];        
        
        if(count($arguments) == 1) $where = $arguments[0]; 
        else foreach($arguments as $value){
            if(!$this->slctIsKey($value)) $value = $this->insrtToString($value);
            $where .= $value;
        }
        if(strlen($where) != 0) $where = " where ".$where;
        
        $array = $this->dbq("select * from ".$this->table.$where);
        foreach($array as $value){
            array_push($selected, $this->arrayToModel($value,$model));
        }
        
        return $selected;
    }
    
    private function slctIsKey($str){
        while($str[strlen($str)-1] == " "){
            $str = substr($str,0,strlen($str)-1);
        }        
        return ($str[strlen($str)-1] == "=");
    } 
    
    private function arrayToModel($arr,$model){
        $modeled = array();
        
        if($model == array()) return $arr;
        
        foreach($model as $key => $value){
            if(!is_array($value)) $modeled[$this->atmValue($key,$arr)] = $this->atmValue($value,$arr);
            else $modeled[$this->atmValue($key,$arr)] = $this->arrayToModel($arr,$value);
        }
        
        return $modeled;
    }
    
    private function atmValue($str,$arr){
        if($str[0] == "@" && strpos($str,'(') !== false){
            $method = substr(strstr($str,")"),1,strlen($str)-1);            
            $ntable = strstr(substr($str,1,strlen($str)-1),"(",true);
            $where = strstr(substr(strstr($str,"("),1,strlen($str)-1),")",true);    
            
            $selected = $this->tables[$ntable]->select($where);
            
            if($method == '.first'){
                if(count($selected) != 0) return $selected[0];
                else return array();
            } else if($method == '.last'){
                if(count($selected) > 1) return $selected[count($selected)-1];
                else if(count($selected) == 1) return $selected[0];
                else return array();
            } else if($method == '.merge'){
                $mrg = array();                
                foreach($selected as $value){
                    $mrg = array_merge($mrg,$value);
                }
                return $mrg;
            }
                    
            return $selected;
        }
        if($str[0] == "@") $str = $arr[substr($str,1,strlen($str)-1)];
        return $str;
    }
    
    function insert($values){
        if(!$this->tableisexist($this->table)) return false;
        
        $cols = '';
        $vals = '';
        foreach($values as $key => $value){
           if($this->colisexist($key)){
              $cols .= $key.", ";
              $vals .= $this->insrtToString($value).", ";
           }
        }        
        $cols = substr($cols,0,strlen($cols)-2);
        $vals = substr($vals,0,strlen($vals)-2);
        
        $before = count($this->select());
        $this->dbq("insert into ".$this->table." (".$cols.") values (".$vals.")");
        return $before != count($this->select());
    }
    
    function update(/*$where, $values*/){
        if(!$this->tableisexist($this->table)) return false;
        
        $arguments = func_get_args();
        $where = "";
        $values = $arguments[count($arguments)-1];
        
        if(count($arguments) == 2 && is_array($arguments[0])) $arguments = $arguments[0];
        
        if(count($arguments) == 1) return false; 
        else if(count($arguments) == 2 && !is_array($arguments[0])) $where = $arguments[0];
        else foreach($arguments as $key => $value){
            if($key != count($arguments)-1){
                if(!$this->slctIsKey($value)) $value = $this->insrtToString($value);
                $where .= $value;
            }
        }
        if(strlen($where) == 0) return false;
        
        $set = '';
        foreach($values as $key => $value){
           if($this->colisexist($key)) $set .= $key." = ".$this->insrtToString($value).", ";
        }        
        $set = substr($set,0,strlen($set)-2);
        $this->dbq("update ".$this->table." set ".$set." where ".$where);
        
        return true;
    }
    
    private function insrtToString($v){
        if($v === true) return "true";
        if($v === false) return "false";
        if(is_string($v) && $v[0] == '@') return substr($v,1,strlen($v)-1);
        if(is_string($v)) return '"'.$this->dbes($v).'"';
        return (string)$v;
    }
    
    function delete($where){
        if(!$this->tableisexist($this->table)) return false;  
        
        $arguments = func_get_args();
        $where = "";
        
        if(count($arguments) == 1 && is_array($arguments[0])) $arguments = $arguments[0];
        
        if(count($arguments) == 1) $where = $arguments[0]; 
        else foreach($arguments as $value){
            if(!$this->slctIsKey($value)) $value = $this->insrtToString($value);
            $where .= $value;
        }
        if(strlen($where) == 0) return false;
        
        $this->dbq("delete from ".$this->table." where ".$where);
        
        $data = $this->select($where);
                
        return (count($data) == 0);
    }
    
    function type($cn){
        if(!$this->tableisexist($this->table)) return false;
        
        $cols = $this->columns();
        
        foreach($cols as $value)
           if($value['Field'] == $cn) return $value['Type'];
           
        return false;
    }
    
    function change($cn,$ncn){
        if(!$this->tableisexist($this->table) || !$this->colisexist($cn) || $this->colisexist($ncn)) return false;
        
        $arguments = func_get_args();
        
        if(count($arguments) == 3) $t = $arguments[2];
        else $t = $ths->type($cn);
        
        $this->dbq("alter table ".$this->table." change ".$cn." ".$ncn." ".$t);
        
        return ($this->type($ncn) == $t);
    }
    
    function add($cs){
        if(!$this->tableisexist($this->table)) return false;
        
        $ret = true;
                
        foreach($cs as $key => $value)
           if(!$this->colisexist($key)) 
               $this->dbq("alter table ".$this->table." add ".$key." ".$value);
           else $ret = false;
               
        return $ret;
    }
    
    function addAfter($cs,$aw){
        if(!$this->tableisexist($this->table) || !$this->colisexist($aw)) return false;
        
        $cs = array_reverse($cs);
        $ret = true;
                
        foreach($cs as $key => $value)
           if(!$this->colisexist($key)) 
               $this->dbq("alter table ".$this->table." add ".$key." ".$value." after ".$aw);
           else $ret = false;
               
        return $ret;
    }
    
    function addFirst($cs){
        if(!$this->tableisexist($this->table)) return false;
        
        $cs = array_reverse($cs);
        $ret = true;
                
        foreach($cs as $key => $value)
           if(!$this->colisexist($key)) 
               $this->dbq("alter table ".$this->table." add ".$key." ".$value." first");
           else $ret = false;
               
        return $ret;
    }
    
    function after($cs,$aw){
        if(!$this->tableisexist($this->table) || !$this->colisexist($aw)) return false;
        
        if(is_string($cs)) $cs = array($cs);
        $cs = array_reverse($cs);
        $ret = true;
                
        foreach($cs as $key => $value)
           if($this->colisexist($key)) 
               $this->dbq("alter table ".$this->table." modify column ".$key." ".$value." after ".$aw);
           else $ret = false;
               
        return $ret;
    }
    
    function first($cs){
        if(!$this->tableisexist($this->table)) return false;
        
        if(is_string($cs)) $cs = array($cs);
        $cs = array_reverse($cs);
        $ret = true;
                
        foreach($cs as $key => $value)
           if(!$this->colisexist($key)) 
               $this->dbq("alter table ".$this->table." add ".$key." ".$value." first");
           else $ret = false;
               
        return $ret;
    }
    
    private function tableisexist($t){
        $ts = $this->dbq('show tables');
        foreach($ts as $value) if(current($value) == $t) return true;
        return false;
    }
    
    function colisexist($c){
        if(!$this->tableisexist($this->table)) return false;
    
        $cs = $this->columns();
        foreach($cs as $value) if($value['Field'] == $c) return true;
        return false;
    }
    
    private function dbconnect($dbconf){
        $this->con = mysql_connect($dbconf['host'],$dbconf['username'],$dbconf['password']);
        if(!$this->con){
           return false;
        }
    
        if(!mysql_select_db($dbconf['dbname'],$this->con)){
           return false;
        }
         
        return true;
    }
    
    private function dbes($str){  //escape string 
        return mysql_real_escape_string($str);     
    }
     
    private function dbq($query){ //query
        $ret = array();
        $q = mysql_query($query,$this->con);        
        while($row = mysql_fetch_assoc($q)) array_push($ret, $row);
         
        return $ret;
    }
 } 

?>