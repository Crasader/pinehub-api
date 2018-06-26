<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateWechatUsersTable.
 */
class CreateWechatUsersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('wechat_users', function(Blueprint $table) {
            $table->increments('id');
            $table->string('app_id')->nullable()->default(null)->comment('程序类型：青食、自提');
            $table->unsignedInteger('user_id')->nullable()->default(null)->comment('用户ID');
            $table->string('wechat_app_id')->nullable()->default(null)->comment('微信公众平台、小程序、开放app id');
            $table->string('type')->default('OFFICE_ACCOUNT')->comment('OFFICE_ACCOUNT 公众平台， 
            OPEN_PLATFORM 开放平台 MINI_PROGRAM 小程序');
            $table->string('union_id')->nullable()->default(null)->comment('union id');
            $table->string('open_id')->comment('open id');
            $table->string('session_key')->comment('session key');
            $table->timestamp('expires_at')->comment('session 过期');
            $table->string('avatar')->nullable()->default(null)->comment('头像');
            $table->string('country')->nullable()->comment('国家');
            $table->string('province')->nullable()->default(null)->comment('省份');
            $table->string('city')->nullable()->default(null)->comment('城市');
            $table->string('nickname')->nullable()->default(null)->comment('用户昵称');
            $table->enum('sex', ['UNKNOWN', 'MALE', 'FEMALE'])->default('UNKNOWN')->comment('性别');
            $table->text('privilege')->nullable()->comment('微信特权信息');
            $table->timestamps();
            $table->softDeletes();
            $table->index('app_id');
            $table->index('wechat_app_id');
            $table->index('union_id');
            $table->index('open_id');
            $table->index('sex');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('wechat_users');
	}
}
