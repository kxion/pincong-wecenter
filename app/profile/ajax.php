<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function avatar_upload_action()
	{
		if (get_setting('upload_enable') == 'N')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('本站未开启上传功能')));
		}

		if (!check_user_operation_interval('profile', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$this->model('avatar')->upload_avatar('aws_upload_file', $this->user_id, $error))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', $error));
		}

		set_user_operation_last_time('profile', $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(array(
			'thumb' => get_setting('upload_url') . '/avatar/' . $this->model('avatar')->get_avatar_path($this->user_id, 'max') . '?' . rand(1, 999)
		), 1, null));
	}


	public function privacy_setting_action()
	{
		if ($notify_actions = $this->model('notification')->notify_action_details)
		{
			$notification_setting = array();

			foreach ($notify_actions as $key => $val)
			{
				if (! isset($_POST['notification_settings'][$key]))
				{
					$notification_setting[] = $key;
				}
			}
		}

		$this->model('account')->update_user_fields(array(
			'inbox_recv' => intval($_POST['inbox_recv'])
		), $this->user_id);

		$this->model('account')->update_notification_setting_fields($notification_setting, $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('隐私设置保存成功')));
	}

	public function profile_setting_action()
	{
		if ($_POST['user_name'])
		{
			$user_name = trim($_POST['user_name']);
			if ($user_name AND $user_name != $this->user_info['user_name'])
			{
				if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_change_username'))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
				}
				if ($check_result = $this->model('register')->check_username_char($user_name))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', $check_result));
				}
				if ($this->model('register')->check_username_sensitive_words($user_name))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名不符合规则')));
				}
				if ($this->model('account')->username_exists($user_name))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经存在相同的姓名, 请重新填写')));
				}
				$this->model('account')->update_user_name($user_name, $this->user_id);

				$this->model('currency')->process($this->user_id, 'CHANGE_USERNAME', get_setting('currency_system_config_change_username'), '修改用户名');
			}
		}

		$update_data['sex'] = intval($_POST['sex']);
		if ($update_data['sex'] < 0 OR $update_data['sex'] > 3)
		{
			$update_data['sex'] = 0;
		}

		$update_data['signature'] = htmlspecialchars($_POST['signature']);

		// 更新主表
		$this->model('account')->update_user_fields($update_data, $this->user_id);

		$this->model('account')->set_default_timezone($_POST['default_timezone'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('个人资料保存成功')));
	}

}
