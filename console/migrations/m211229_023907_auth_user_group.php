<?php

use yii\db\Migration;

class m211229_023907_auth_user_group extends Migration
{
    public function up()
    {
        /* 取消外键约束 */
        $this->execute('SET foreign_key_checks = 0');
        
        /* 创建表 */
        $this->createTable('{{%auth_user_group}}', [
            'id' => "int(11) NOT NULL AUTO_INCREMENT",
            'name' => "varchar(64) NOT NULL COMMENT '用户组名称'",
            'module_name' => "varchar(255) NULL",
            'type' => "smallint(6) NOT NULL COMMENT '用户组类型0系统1商户'",
            'description' => "text NULL COMMENT '用户组名称'",
            'bloc_id' => "int(11) NULL COMMENT '公司'",
            'store_id' => "int(11) NULL COMMENT '商户'",
            'created_at' => "int(11) NULL",
            'updated_at' => "int(11) NULL",
            'PRIMARY KEY (`id`,`name`)'
        ], "ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='后台用户组'");
        
        /* 索引设置 */
        $this->createIndex('type','{{%auth_user_group}}','type',0);
        $this->createIndex('name','{{%auth_user_group}}','name',0);
        
        
        /* 表数据 */
        $this->insert('{{%auth_user_group}}',['id'=>'1','name'=>'总管理员','module_name'=>'sys','type'=>'0','description'=>'总管理员','bloc_id'=>'0','store_id'=>'0','created_at'=>'1640699561','updated_at'=>'1640699561']);
        $this->insert('{{%auth_user_group}}',['id'=>'2','name'=>'基础权限组','module_name'=>'sys','type'=>'0','description'=>'基础权限','bloc_id'=>'0','store_id'=>'0','created_at'=>'1640699764','updated_at'=>'1640699764']);
        
        /* 设置外键约束 */
        $this->execute('SET foreign_key_checks = 1;');
    }

    public function down()
    {
        $this->execute('SET foreign_key_checks = 0');
        /* 删除表 */
        $this->dropTable('{{%auth_user_group}}');
        $this->execute('SET foreign_key_checks = 1;');
    }
}

