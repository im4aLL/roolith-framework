<?php
use App\Core\Storage;
use Carbon\Carbon;

/**
 * Carbon diff for humans
 *
 * @param string $dateString
 * @return string
 */
function diffForHumans(string $dateString): string
{
    return Carbon::parse($dateString)->diffForHumans();
}

/**
 * To date time string
 *
 * @param string $dateString
 * @return string
 */
function toDateTimeString(string $dateString): string
{
    return Carbon::parse($dateString)->toDayDateTimeString();
}

/**
 * Get module image URL
 *
 * @param string $imageName
 * @return string
 */
function getModuleImageUrl(string $imageName): string
{
    return url(APP_ADMIN_FILE_MANAGER_MODULE_DATA_DIR . $imageName);
}

/**
 * Whether data changed in an associative array
 *
 * @param array $updatedDataArray
 * @param array $originalDataArray
 * @return bool
 */
function isDataChanged(array $updatedDataArray, array $originalDataArray): bool
{
    $isChanged = false;

    foreach ($updatedDataArray as $key => $value) {
        if ($value != $originalDataArray[$key]) {
            $isChanged = true;
            break;
        }
    }

    return $isChanged;
}

/**
 * Get icon HTML by extension
 *
 * @param string $extension
 * @return string
 */
function getIconHtmlByExtension(string $extension = ''): string
{
    return match (strtolower($extension)) {
        'jpg', 'jpeg', 'png' => '<i class="iconoir-media-image"></i>',
        'pdf' => '<i class="iconoir-page"></i>',
        'doc', 'docx' => '<i class="iconoir-google-docs"></i>',
        'zip' => '<i class="iconoir-attachment"></i>',
        'xls', 'xlsx' => '<i class="iconoir-doc-star"></i>',
        'csv' => '<i class="iconoir-code-brackets"></i>',
        'ppt', 'pptx' => '<i class="iconoir-multiple-pages"></i>',
        default => '<i class="iconoir-folder"></i>',
    };
}

/**
 * Get ui state by key
 *
 * @param string $key
 * @return mixed
 */
function getUiStateByKey(string $key): mixed
{
    return Storage::getCookie($key);
}

/**
 * Get gravatar url
 *
 * @return string
 */
function getGravatarUrl(): string
{
    $email = Storage::getSession(APP_ADMIN_SESSION_KEY);
    $hash = hash('sha256', strtolower(trim($email)));

    return "https://www.gravatar.com/avatar/$hash";
}
