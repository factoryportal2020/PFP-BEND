<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('admin_id');
            $table->bigInteger('domain_id');
            $table->string('title', 100);
            $table->bigInteger('worker_id');
            $table->bigInteger('customer_id')->nullable();
            $table->bigInteger('category_id');
            $table->string('specification', 100)->nullable();
            $table->string('price', 100)->nullable();
            $table->string('quantity', 100)->default(1);
            $table->string('description', 1000)->nullable();
            $table->string('code', 50);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->enum('status', ['Unassigned', 'Assigned', 'Inprogress', 'Holding', 'Restarted', 'Cancelled', 'Pending', 'Completed', 'Delivered'])->default('Unassigned');
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('task_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id');
            $table->string('name', 100);
            $table->string('path', 255);
            $table->string('type', 100)->nullable();
            $table->string('extension', 100)->default('image');
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
            $table->timestamps();
        });

        Schema::create('task_specifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id');
            $table->string('label_name', 100);
            $table->string('type', 20)->nullable();
            $table->string('value', 100);
        });

        Schema::create('task_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id');
            $table->string('label_name', 100);
            $table->string('value', 100);
        });

        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id');
            $table->enum('status', ['Unassigned', 'Assigned', 'Inprogress', 'Holding', 'Restarted', 'Cancelled', 'Pending', 'Completed', 'Delivered']);
            $table->string('comment', 250);
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
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_images');
        Schema::dropIfExists('task_specifications');
        Schema::dropIfExists('task_breakdowns');
        Schema::dropIfExists('task_histories');
    }
}
