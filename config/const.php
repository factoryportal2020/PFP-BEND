<?php


return [
    'admin' => 'public/admin/profile',
    'customer' => 'public/customer/profile',
    'worker' => 'public/worker/profile',
    'category' => 'public/category',
    'item' => 'public/item',
    'task' => 'public/task',
    'website' => 'public/website',
    'banner' => 'public/defaults/jewell/banner',
    'about' => 'public/defaults/jewell/about',
    'feature' => 'public/defaults/jewell/feature',

    // Notification Message
    // Website Message to admin
    'favourite' => ['link' => 'favourite/list', 'message' => "%s product added to favourite list"],
    'subscribe' => ['link' => 'subscribe/list', 'message' => "%s - subscriber added"],
    'enquiry' => ['link' => 'enquiry/list', 'message' => "Product %s received enquiry"],
    'message' => ['link' => 'message/list', 'message' => "Received Message from Customer"],

    // From Admin to worker
    'taskAssinged' => ['link' => 'task/%s', 'message' => "Task %s assigned to you"],
    'taskUpdated' => ['link' => 'task/%s', 'message' => "Task %s status have updated to %s"],

    // From Worker to Admin
    'taskUpdated' => ['link' => 'task/%s', 'message' => "%s have changed task %s status to %s"],

];
