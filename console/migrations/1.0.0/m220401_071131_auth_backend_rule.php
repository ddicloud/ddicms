<?php

use yii\db\Migration;

class m220401_071131_auth_backend_rule extends Migration
{
    public function up()
    {
        /* 取消外键约束 */
        $this->execute('SET foreign_key_checks = 0');
        
        /* 创建表 */
        $this->createTable('{{%auth_backend_rule}}', [
            'id' => "int(11) NOT NULL AUTO_INCREMENT",
            'name' => "varchar(64) NOT NULL",
            'data' => "blob NULL",
            'created_at' => "int(11) NULL",
            'updated_at' => "int(11) NULL",
            'PRIMARY KEY (`id`,`name`)'
        ], "ENGINE=InnoDB  DEFAULT CHARSET=utf8");
        
        /* 索引设置 */
        
        
        /* 表数据 */
        $this->insert('{{%auth_backend_rule}}',['id'=>'1','name'=>'模块访问1','data'=>'O:22:"common\rbac\AddonsRule":3:{s:4:"name";s:12:"模块访问";s:9:"createdAt";i:1588462049;s:9:"updatedAt";i:1588462170;}','created_at'=>'1588462049','updated_at'=>'1588462170']);
        
        /* 设置外键约束 */
        $this->execute('SET foreign_key_checks = 1;');
    }

    public function down()
    {
        $this->execute('SET foreign_key_checks = 0');
        /* 删除表 */
        $this->dropTable('{{%auth_backend_rule}}');
        $this->execute('SET foreign_key_checks = 1;');
    }
}
