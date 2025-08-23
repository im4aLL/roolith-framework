<?php

/**
 * Session key for the admin user.
 */
const APP_ADMIN_SESSION_KEY = 'roolithAdminUser';

/**
 * Number of items per page in admin grid view.
 */
const APP_ADMIN_PAGINATION_PER_PAGE = 5; // 23

/**
 * Where the admin file manager is stored.
 */
const APP_ADMIN_FILE_MANAGER_DIR = 'files/';
const APP_ADMIN_FILE_MANAGER_MODULE_DATA_DIR = APP_ADMIN_FILE_MANAGER_DIR . 'uploads/';

/**
 * Allow file types
 */
const APP_ADMIN_ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png'];
const APP_ADMIN_ALLOWED_FILE_TYPES = ['pdf', 'doc', 'docx', 'zip', 'xls', 'xlsx', 'csv', 'ppt', 'pptx'];
