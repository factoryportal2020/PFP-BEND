<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsite extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('domain_id');
            $table->bigInteger('admin_id');
            $table->string('company_name', 255)->nullable();
            $table->string('site_url', 400)->nullable();
            $table->string('email', 255)->unique();
            $table->string('phone_no', 50)->unique();
            $table->string('landline_no', 50)->nullable();
            $table->string('whatsapp_no', 50)->nullable();
            $table->string('address', 1000)->nullable();
            $table->string('instagram_link', 400)->nullable();
            $table->string('facebook_link', 400)->nullable();
            $table->string('twitter_link', 400)->nullable();
            $table->string('code', 50);
            $table->tinyInteger('status')->default(0);
            $table->dateTime('launch_at')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('website_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('website_id');
            $table->string('name', 100);
            $table->string('path', 255);
            $table->string('size',50)->nullable();
            $table->string('type', 100)->nullable();
            $table->string('title', 500)->nullable();
            $table->string('caption', 500)->nullable();
            $table->text('detail')->nullable();
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
            $table->timestamp('delete_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('websites');
        Schema::dropIfExists('website_image');
    }
}
