/*
Navicat MySQL Data Transfer

Source Server         : test
Source Server Version : 50542
Source Host           : 112.124.38.59:3306
Source Database       : work

Target Server Type    : MYSQL
Target Server Version : 50542
File Encoding         : 65001

Date: 2017-09-30 17:36:32
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for v1_process_extends_2
-- ----------------------------
DROP TABLE IF EXISTS `v1_process_extends_2`;
CREATE TABLE `v1_process_extends_2` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `process_id` int(11) DEFAULT NULL,
  `process_instance_id` int(11) DEFAULT '0' COMMENT '流程实例ID',
  `csr_id` varchar(50) DEFAULT NULL COMMENT '车商融申请单id',
  `customer_user_id` varchar(50) DEFAULT NULL COMMENT '用户id',
  `money_request` varchar(50) DEFAULT NULL COMMENT '融资金额',
  `car_stock_request` varchar(50) DEFAULT NULL COMMENT '库存车辆',
  `deadline_request` varchar(50) DEFAULT NULL COMMENT '融资期限',
  `rate_request` varchar(50) DEFAULT NULL COMMENT '融资利率',
  `money` varchar(50) DEFAULT NULL,
  `car_stock` varchar(50) DEFAULT NULL,
  `deadline` varchar(50) DEFAULT NULL,
  `rate` varchar(50) DEFAULT NULL,
  `create_time` varchar(11) DEFAULT NULL,
  `modify_time` varchar(11) DEFAULT NULL,
  `csr_no` varchar(50) DEFAULT NULL COMMENT '融资单号',
  `csrrequest_user_id` varchar(50) DEFAULT NULL COMMENT '融资申请处理者',
  `name` varchar(50) DEFAULT NULL COMMENT '客户姓名',
  `shopname` varchar(100) DEFAULT NULL COMMENT '店铺名称',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=833 DEFAULT CHARSET=utf8;
