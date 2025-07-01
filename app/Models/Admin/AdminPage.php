<?php
namespace App\Models\Admin;

use App\Models\Model;

class AdminPage extends Model
{
    protected string $table = 'pages';
    const publishedStatus = 'published';
    const draftStatus = 'draft';
}
