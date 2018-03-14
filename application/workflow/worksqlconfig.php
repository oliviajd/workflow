配置表
v1_process
v1_process_item
v1_process_link
/*
Navicat MySQL Data Transfer

Source Server         : test
Source Server Version : 50542
Source Host           : 112.124.38.59:3306
Source Database       : work

Target Server Type    : MYSQL
Target Server Version : 50542
File Encoding         : 65001

Date: 2017-09-30 17:36:24
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for v1_process
-- ----------------------------
DROP TABLE IF EXISTS `v1_process`;
CREATE TABLE `v1_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '任务名称',
  `node_type` varchar(255) NOT NULL COMMENT 'event,task,gateway中的一个',
  `node_id` int(11) NOT NULL COMMENT '对应各表中的ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of v1_process
-- ----------------------------
INSERT INTO `v1_process` VALUES ('1', '车贷流程', '0', '0');
INSERT INTO `v1_process` VALUES ('2', '融资申请', '0', '0');

/*
Navicat MySQL Data Transfer

Source Server         : test
Source Server Version : 50542
Source Host           : 112.124.38.59:3306
Source Database       : work

Target Server Type    : MYSQL
Target Server Version : 50542
File Encoding         : 65001

Date: 2017-09-30 17:36:52
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for v1_process_item
-- ----------------------------
DROP TABLE IF EXISTS `v1_process_item`;
CREATE TABLE `v1_process_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process_id` int(11) NOT NULL DEFAULT '0' COMMENT '流程ID',
  `title` varchar(255) NOT NULL COMMENT '任务名称',
  `node_type` varchar(255) NOT NULL COMMENT 'event,task,gateway中的一个',
  `node_id` int(11) NOT NULL COMMENT '对应各表中的ID',
  `role_id` varchar(127) NOT NULL DEFAULT '0' COMMENT '可拾取该任务的角色ID，0表示所有角色可以拾取，多个角色 用逗号隔开',
  `condition` varchar(255) DEFAULT '' COMMENT '上个节点为网关时，用于记录网关流向的条件',
  `sort` int(11) NOT NULL DEFAULT '0',
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of v1_process_item
-- ----------------------------
INSERT INTO `v1_process_item` VALUES ('1', '1', '流程开始', 'event', '0', '0', '', '1', null);
INSERT INTO `v1_process_item` VALUES ('2', '1', '经销商申请按揭', 'task', '0', '72', '', '2', null);
INSERT INTO `v1_process_item` VALUES ('3', '1', '银行驻点人员查询征信', 'task', '0', '80', '', '3', null);
INSERT INTO `v1_process_item` VALUES ('4', '1', '征信查询结果是否通过', 'gateway', '0', '0', '', '4', null);
INSERT INTO `v1_process_item` VALUES ('5', '1', '征信拒件', 'event', '0', '0', '$inquire_status == 2', '5', null);
INSERT INTO `v1_process_item` VALUES ('6', '1', '流程中止', 'end', '0', '0', '', '6', null);
INSERT INTO `v1_process_item` VALUES ('7', '1', '家访签约', 'task', '0', '98', '$inquire_status == 1', '7', null);
INSERT INTO `v1_process_item` VALUES ('8', '1', '录入员录入档案', 'task', '0', '88', '$visit_status == 1', '11', null);
INSERT INTO `v1_process_item` VALUES ('9', '1', '人工审批（一审）', 'task', '0', '82', '$inputrequest_status == 1 || $supplement_sale_one_status == 1', '11', null);
INSERT INTO `v1_process_item` VALUES ('10', '1', '一审是否通过', 'gateway', '0', '0', '', '12', null);
INSERT INTO `v1_process_item` VALUES ('11', '1', '家访拒件', 'event', '0', '0', '$visit_status == 2', '9', null);
INSERT INTO `v1_process_item` VALUES ('12', '1', '流程中止', 'end', '0', '0', '', '10', null);
INSERT INTO `v1_process_item` VALUES ('13', '1', '补充资料（家访）', 'task', '0', '99', '($artificialone_status == 3 || $artificialtwo_status == 3) && $supplement_visit==1', '15', '所有补充完资料任务后继续进入一审任务');
INSERT INTO `v1_process_item` VALUES ('14', '1', '二审拒件', 'event', '0', '0', '$artificialtwo_status == 2', '19', '');
INSERT INTO `v1_process_item` VALUES ('15', '1', '二审是否通过', 'gateway', '0', '0', '', '18', null);
INSERT INTO `v1_process_item` VALUES ('16', '1', '流程中止', 'end', '0', '0', '', '20', null);
INSERT INTO `v1_process_item` VALUES ('32', '1', '流程结束', 'event', '0', '0', '', '27', null);
INSERT INTO `v1_process_item` VALUES ('34', '1', '家访是否通过', 'gateway', '0', '0', '', '8', null);
INSERT INTO `v1_process_item` VALUES ('35', '1', '一审拒件', 'event', '0', '0', '$artificialone_status == 2', '13', null);
INSERT INTO `v1_process_item` VALUES ('36', '1', '流程中止', 'end', '0', '0', '', '14', null);
INSERT INTO `v1_process_item` VALUES ('37', '1', '人工审批（二审）', 'task', '0', '83', '($artificialone_status == 1 && $loan_prize >=0) || $supplement_sale_two_status == 1', '17', null);
INSERT INTO `v1_process_item` VALUES ('39', '1', '财务打款', 'task', '0', '86', '$moneyaudit_status == 1', '21', null);
INSERT INTO `v1_process_item` VALUES ('40', '1', '回款确认', 'task', '0', '87', '', '22', null);
INSERT INTO `v1_process_item` VALUES ('41', '1', '寄件登记', 'task', '0', '92', '$artificialtwo_status == 1 || ($artificialone_status == 1 && $loan_prize <0)', '23', null);
INSERT INTO `v1_process_item` VALUES ('42', '1', '抄单登记', 'task', '0', '93', '', '24', null);
INSERT INTO `v1_process_item` VALUES ('43', '1', '车辆GPS登记', 'task', '0', '94', '', '25', null);
INSERT INTO `v1_process_item` VALUES ('44', '1', '抵押登记', 'task', '0', '95', '', '26', null);
INSERT INTO `v1_process_item` VALUES ('45', '1', '补充资料（销售）', 'task', '0', '155', '($artificialone_status == 3 || $artificialtwo_status == 3) && $supplement_salesman==1', '16', '所有补充完资料任务后继续进入一审任务');
INSERT INTO `v1_process_item` VALUES ('46', '1', '是否补件完成', 'gateway', '0', '0', '(!$supplement_visit || $supplement_visit == 3) && (!$supplement_salesman || $supplement_salesman == 3)', '16', null);
INSERT INTO `v1_process_item` VALUES ('47', '1', '申请打款', 'task', '0', '168', '$artificialtwo_status == 1 || ($artificialone_status == 1 && $loan_prize <0)', '0', null);
INSERT INTO `v1_process_item` VALUES ('48', '1', '打款审核', 'task', '0', '174', '', '0', null);
INSERT INTO `v1_process_item` VALUES ('49', '1', '打款审核是否通过', 'gateway', '0', '0', '', '0', null);
INSERT INTO `v1_process_item` VALUES ('50', '1', '打款审核拒件', 'event', '0', '0', '$moneyaudit_status == 2', '0', null);
INSERT INTO `v1_process_item` VALUES ('51', '1', '流程终止', 'end', '0', '0', '', '0', null);
INSERT INTO `v1_process_item` VALUES ('52', '2', '流程开始', 'event', '0', '0', '', '0', null);
INSERT INTO `v1_process_item` VALUES ('53', '2', '融资申请', 'task', '0', '201', '', '0', null);
INSERT INTO `v1_process_item` VALUES ('54', '2', '融资申请后台审核', 'task', '0', '202', '', '0', null);
INSERT INTO `v1_process_item` VALUES ('55', '2', '融资申请审核是否通过', 'gateway', '0', '0', '', '0', null);
INSERT INTO `v1_process_item` VALUES ('56', '2', '融资申请拒绝', 'event', '0', '0', '$crsrequest_status == 2', '0', null);
INSERT INTO `v1_process_item` VALUES ('57', '2', '流程中止', 'end', '0', '0', '', '0', null);
INSERT INTO `v1_process_item` VALUES ('58', '2', '上标申请', 'task', '0', '203', '$crsrequest_status == 1', '0', null);
INSERT INTO `v1_process_item` VALUES ('59', '2', '流程终止', 'end', '0', '0', '', '0', null);

/*
Navicat MySQL Data Transfer

Source Server         : test
Source Server Version : 50542
Source Host           : 112.124.38.59:3306
Source Database       : work

Target Server Type    : MYSQL
Target Server Version : 50542
File Encoding         : 65001

Date: 2017-09-30 17:37:12
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for v1_process_link
-- ----------------------------
DROP TABLE IF EXISTS `v1_process_link`;
CREATE TABLE `v1_process_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process_id` int(11) NOT NULL DEFAULT '0',
  `current_id` int(11) NOT NULL DEFAULT '0' COMMENT '当前任务进度ID',
  `prev_id` int(11) NOT NULL DEFAULT '0' COMMENT '前置任务的ID',
  `next_id` int(11) NOT NULL DEFAULT '0' COMMENT '下一个任务进度ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of v1_process_link
-- ----------------------------
INSERT INTO `v1_process_link` VALUES ('1', '1', '1', '0', '2');
INSERT INTO `v1_process_link` VALUES ('2', '1', '2', '1', '3');
INSERT INTO `v1_process_link` VALUES ('3', '1', '3', '2', '4');
INSERT INTO `v1_process_link` VALUES ('4', '1', '4', '3', '5');
INSERT INTO `v1_process_link` VALUES ('5', '1', '5', '4', '6');
INSERT INTO `v1_process_link` VALUES ('6', '1', '4', '3', '7');
INSERT INTO `v1_process_link` VALUES ('7', '1', '7', '4', '34');
INSERT INTO `v1_process_link` VALUES ('8', '1', '34', '7', '11');
INSERT INTO `v1_process_link` VALUES ('9', '1', '11', '34', '12');
INSERT INTO `v1_process_link` VALUES ('10', '1', '8', '34', '9');
INSERT INTO `v1_process_link` VALUES ('11', '1', '9', '8', '10');
INSERT INTO `v1_process_link` VALUES ('12', '1', '10', '9', '35');
INSERT INTO `v1_process_link` VALUES ('13', '1', '35', '10', '36');
INSERT INTO `v1_process_link` VALUES ('14', '1', '13', '10', '46');
INSERT INTO `v1_process_link` VALUES ('15', '1', '37', '10', '15');
INSERT INTO `v1_process_link` VALUES ('16', '1', '15', '37', '14');
INSERT INTO `v1_process_link` VALUES ('17', '1', '14', '15', '16');
INSERT INTO `v1_process_link` VALUES ('18', '1', '13', '15', '46');
INSERT INTO `v1_process_link` VALUES ('19', '1', '39', '15', '43');
INSERT INTO `v1_process_link` VALUES ('24', '1', '34', '7', '8');
INSERT INTO `v1_process_link` VALUES ('25', '1', '10', '9', '37');
INSERT INTO `v1_process_link` VALUES ('26', '1', '10', '9', '13');
INSERT INTO `v1_process_link` VALUES ('27', '1', '10', '9', '47');
INSERT INTO `v1_process_link` VALUES ('29', '1', '15', '37', '13');
INSERT INTO `v1_process_link` VALUES ('30', '1', '15', '37', '47');
INSERT INTO `v1_process_link` VALUES ('31', '1', '45', '10', '46');
INSERT INTO `v1_process_link` VALUES ('32', '1', '45', '15', '46');
INSERT INTO `v1_process_link` VALUES ('33', '1', '10', '9', '45');
INSERT INTO `v1_process_link` VALUES ('34', '1', '15', '37', '45');
INSERT INTO `v1_process_link` VALUES ('35', '1', '41', '15', '42');
INSERT INTO `v1_process_link` VALUES ('36', '1', '42', '41', '44');
INSERT INTO `v1_process_link` VALUES ('37', '1', '44', '42', '40');
INSERT INTO `v1_process_link` VALUES ('38', '1', '15', '37', '41');
INSERT INTO `v1_process_link` VALUES ('41', '1', '46', '13', '9');
INSERT INTO `v1_process_link` VALUES ('43', '1', '10', '9', '41');
INSERT INTO `v1_process_link` VALUES ('44', '1', '47', '15', '48');
INSERT INTO `v1_process_link` VALUES ('45', '1', '48', '47', '49');
INSERT INTO `v1_process_link` VALUES ('46', '1', '49', '48', '50');
INSERT INTO `v1_process_link` VALUES ('47', '1', '50', '49', '36');
INSERT INTO `v1_process_link` VALUES ('48', '1', '49', '48', '39');
INSERT INTO `v1_process_link` VALUES ('51', '1', '46', '45', '9');
INSERT INTO `v1_process_link` VALUES ('52', '1', '46', '45', '37');
INSERT INTO `v1_process_link` VALUES ('53', '2', '52', '0', '53');
INSERT INTO `v1_process_link` VALUES ('54', '2', '53', '52', '54');
INSERT INTO `v1_process_link` VALUES ('55', '2', '54', '53', '55');
INSERT INTO `v1_process_link` VALUES ('56', '2', '55', '54', '56');
INSERT INTO `v1_process_link` VALUES ('57', '2', '56', '55', '57');
INSERT INTO `v1_process_link` VALUES ('58', '2', '55', '54', '58');

