ModulerDB
=========

Convenient operation and access to MySQL database.

Methods
=========

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
 
    bool $db['table199']->create() - creates new table with name 'table199';

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