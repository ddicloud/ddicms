<?php

use yii\db\Migration;

class m220228_021727_bloc extends Migration
{
    public function up()
    {
        /* 取消外键约束 */
        $this->execute('SET foreign_key_checks = 0');
        
        /* 创建表 */
        $this->createTable('{{%bloc}}', [
            'bloc_id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
            'business_name' => "varchar(50) NOT NULL COMMENT '公司名称'",
            'pid' => "int(11) NOT NULL DEFAULT '0' COMMENT '上级商户'",
            'group_bloc_id' => "int(11) NOT NULL DEFAULT '0' COMMENT '集团ID'",
            'category' => "varchar(255) NOT NULL DEFAULT '0'",
            'province' => "varchar(15) NOT NULL COMMENT '省份'",
            'city' => "varchar(15) NOT NULL COMMENT '城市'",
            'district' => "varchar(15) NOT NULL COMMENT '区县'",
            'address' => "varchar(50) NOT NULL COMMENT '具体地址'",
            'register_level' => "int(11) NOT NULL COMMENT '注册级别'",
            'longitude' => "varchar(15) NOT NULL COMMENT '经度'",
            'latitude' => "varchar(15) NOT NULL COMMENT '纬度'",
            'telephone' => "varchar(20) NOT NULL COMMENT '电话'",
            'avg_price' => "int(10) unsigned NOT NULL",
            'recommend' => "varchar(255) NOT NULL COMMENT '介绍'",
            'special' => "varchar(255) NOT NULL COMMENT '特色'",
            'introduction' => "varchar(255) NOT NULL COMMENT '详细介绍'",
            'open_time' => "varchar(50) NOT NULL COMMENT '开业时间'",
            'status' => "tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0 待审核 1 审核通过 2 审核未通过'",
            'is_group' => "tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否是集团'",
            'sosomap_poi_uid' => "varchar(50) NOT NULL DEFAULT '' COMMENT '腾讯地图标注id'",
            'license_no' => "varchar(30) NOT NULL DEFAULT '' COMMENT '营业执照注册号或组织机构代码'",
            'license_name' => "varchar(100) NOT NULL DEFAULT '' COMMENT '营业执照名称'",
            'level_num' => "int(11) NULL COMMENT '等级'",
            'store_id' => "int(11) NOT NULL DEFAULT '0'",
            'other_files' => "text NULL COMMENT '其他文件'",
            'extra' => "text NULL COMMENT '我的家族'",
            'PRIMARY KEY (`bloc_id`)'
        ], "ENGINE=InnoDB  DEFAULT CHARSET=utf8");
        
        /* 索引设置 */
        
        
        /* 表数据 */
        $this->insert('{{%bloc}}',['bloc_id'=>'8','business_name'=>'店滴官网','pid'=>'0','group_bloc_id'=>'8','category'=>'0','province'=>'2367','city'=>'2368','district'=>'2373','address'=>'4536','register_level'=>'0','longitude'=>'456','latitude'=>'567','telephone'=>'5676575','avg_price'=>'20','recommend'=>'34','special'=>'乐乐巴布','introduction'=>'234535','open_time'=>'234','status'=>'0','is_group'=>'1','sosomap_poi_uid'=>'','license_no'=>'23425','license_name'=>'5467','level_num'=>NULL,'store_id'=>'61','other_files'=>NULL,'extra'=>'s:48:"s:40:"s:32:"s:24:"s:16:"s:9:"s:2:"N;";";";";";";";']);
        $this->insert('{{%bloc}}',['bloc_id'=>'12','business_name'=>'客户演示','pid'=>'8','group_bloc_id'=>'8','category'=>'0','province'=>'801','city'=>'802','district'=>'806','address'=>'3243','register_level'=>'0','longitude'=>'3435','latitude'=>'45','telephone'=>'5656','avg_price'=>'10','recommend'=>'2112','special'=>'4345','introduction'=>'3434','open_time'=>'23','status'=>'0','is_group'=>'0','sosomap_poi_uid'=>'','license_no'=>'343','license_name'=>'56','level_num'=>NULL,'store_id'=>'0','other_files'=>NULL,'extra'=>NULL]);
        $this->insert('{{%bloc}}',['bloc_id'=>'27','business_name'=>'和生科技','pid'=>'0','group_bloc_id'=>'0','category'=>'0','province'=>'2670','city'=>'2724','district'=>'2725','address'=>'54','register_level'=>'1','longitude'=>'116.494738','latitude'=>'39.871065','telephone'=>'2323','avg_price'=>'32','recommend'=>'23','special'=>'34','introduction'=>'324354','open_time'=>'1632326400','status'=>'1','is_group'=>'0','sosomap_poi_uid'=>'','license_no'=>'3434','license_name'=>'2323','level_num'=>'2','store_id'=>'0','other_files'=>'','extra'=>NULL]);
        $this->insert('{{%bloc}}',['bloc_id'=>'28','business_name'=>'晓多','pid'=>'0','group_bloc_id'=>'0','category'=>'0','province'=>'2367','city'=>'2440','district'=>'2443','address'=>'四川省','register_level'=>'1','longitude'=>'116.383779','latitude'=>'39.88081','telephone'=>'312','avg_price'=>'12','recommend'=>'23','special'=>'23','introduction'=>'详细介绍','open_time'=>'1636646400','status'=>'1','is_group'=>'0','sosomap_poi_uid'=>'','license_no'=>'23','license_name'=>'1223','level_num'=>NULL,'store_id'=>'0','other_files'=>NULL,'extra'=>NULL]);
        $this->insert('{{%bloc}}',['bloc_id'=>'29','business_name'=>'四川党建','pid'=>'0','group_bloc_id'=>'0','category'=>'0','province'=>'0','city'=>'0','district'=>'0','address'=>'1','register_level'=>'1','longitude'=>'1','latitude'=>'11','telephone'=>'1','avg_price'=>'1','recommend'=>'1','special'=>'11','introduction'=>'1','open_time'=>'11','status'=>'2','is_group'=>'0','sosomap_poi_uid'=>'','license_no'=>'','license_name'=>'','level_num'=>NULL,'store_id'=>'0','other_files'=>NULL,'extra'=>NULL]);
        
        /* 设置外键约束 */
        $this->execute('SET foreign_key_checks = 1;');
    }

    public function down()
    {
        $this->execute('SET foreign_key_checks = 0');
        /* 删除表 */
        $this->dropTable('{{%bloc}}');
        $this->execute('SET foreign_key_checks = 1;');
    }
}
