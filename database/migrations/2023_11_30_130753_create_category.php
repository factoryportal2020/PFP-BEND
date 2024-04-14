<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('admin_id');
            $table->bigInteger('domain_id');
            $table->string('name',100);
            $table->string('description',1000)->nullable();
            $table->string('code',50);
            $table->tinyInteger('status')->default(1);
            $table->string('sub_title');
            $table->tinyInteger('is_show')->default(1);
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('category_id');
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
        Schema::dropIfExists('category');
    }
}
