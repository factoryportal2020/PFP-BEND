<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('admin_id');
            $table->bigInteger('domain_id');
            $table->string('first_name',100)->nullable();
            $table->string('last_name',100)->nullable();
            $table->string('email',255)->unique();
            $table->string('username',150)->nullable();
            $table->string('gender',25)->nullable();
            $table->string('phone_no',50)->unique();
            $table->string('whatsapp_no',50)->nullable();
            $table->string('instagram_id',100)->nullable();
            $table->string('address',1000)->nullable();
            $table->string('city',100)->nullable();
            $table->string('state',100)->nullable();
            $table->string('notes',1000)->nullable();
            $table->string('code',50);
            $table->tinyInteger('status')->default(1);
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id');
            $table->string('name',100);
            $table->string('path',255);
            $table->string('size',50)->nullable();
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
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
        Schema::dropIfExists('customers');
    }
}
