<?php
class Storage {
    private $data_dir = 'data/';
    private $tabs_dir = 'data/tabs/';
    private $active_tabs_dir = 'data/tabs/active/';
    private $deleted_tabs_dir = 'data/tabs/deleted/';
    private $uploads_dir = 'uploads/';
    private $user_settings_dir = 'data/user_settings/';
    
    private $tabs_index_file = 'data/tabs_index.json';
    private $users_file = 'data/users.json';
    private $replies_file = 'data/replies.json';
    private $files_index_file = 'data/files_index.json';
    
    private $tabs_index = [];
    private $users_data = [];
    private $replies_data = [];
    private $files_index = [];
     
    private $colors = [
        // Светлые (16 цветов)
        '#FFFFFF', '#FFD6D6', '#D6FFD6', '#D6D6FF', '#FFE6CC', '#E6D6FF',
        '#CCE5FF', '#FFD6F0', '#D6FFE6', '#FFF0CC', '#D6F0FF', '#F0D6D6',
        '#D6F0F0', '#FFD6E6', '#F0F0CC', '#E6E6FA',
        // Средние (12 цветов) - 40%
        '#FFB3B3', '#B3FFB3', '#B3B3FF', '#FFCCB3', '#CCB3FF',
        '#B3CCFF', '#FFB3E6', '#B3FFCC', '#FFE6B3', '#B3E6FF',
        '#E6B3B3', '#B3E6E6',
        // Яркие (12 цветов) - 70%
        '#FF8080', '#80FF80', '#8080FF', '#FFB380', '#B380FF',
        '#80B3FF', '#FF80D9', '#80FFB3', '#FFD980', '#80D9FF',
        '#D98080', '#80D9D9',
        // Насыщенные (12 цветов) - 90%
        '#FF4D4D', '#4DFF4D', '#4D4DFF', '#FF994D', '#994DFF',
        '#4D99FF', '#FF4DCC', '#4DFF99', '#FFCC4D', '#4DCCFF',
        '#CC4D4D', '#4DCCCC'
    ];
    
    private $default_titles = ['Сообщение', 'Задача', 'Замечание', 'Примечание', 'План', 'Отчет'];
    private $default_objects = ['', 'Проект', 'Задача', 'Ошибка', 'Требование', 'Документ'];
    
    public function __construct() {
        $this->createDirectories();
        $this->loadUsers();
        $this->loadReplies();
        $this->loadTabsIndex();
        $this->loadFilesIndex();
        
        if (empty($this->users_data)) {
            $this->createAdmin();
        }
        
        if (empty($this->tabs_index)) {
            $this->createDefaultTabs();
        }
    }
    
    private function createDirectories() {
        $dirs = [
            $this->data_dir,
            $this->tabs_dir,
            $this->active_tabs_dir,
            $this->deleted_tabs_dir,
            $this->uploads_dir,
            $this->user_settings_dir
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
    
    private function loadUsers() {
        if (file_exists($this->users_file)) {
            $this->users_data = json_decode(file_get_contents($this->users_file), true) ?: [];
        }
        if (!is_array($this->users_data)) $this->users_data = [];
    }
    
    private function loadReplies() {
        if (file_exists($this->replies_file)) {
            $this->replies_data = json_decode(file_get_contents($this->replies_file), true) ?: [];
        }
        if (!is_array($this->replies_data)) $this->replies_data = [];
    }
    
    private function loadTabsIndex() {
        if (file_exists($this->tabs_index_file)) {
            $this->tabs_index = json_decode(file_get_contents($this->tabs_index_file), true) ?: [];
        }
        if (!is_array($this->tabs_index)) $this->tabs_index = [];
    }
    
    private function loadFilesIndex() {
        if (file_exists($this->files_index_file)) {
            $this->files_index = json_decode(file_get_contents($this->files_index_file), true) ?: [];
        }
        if (!is_array($this->files_index)) $this->files_index = [];
    }
    
    private function saveUsers() {
        file_put_contents($this->users_file, json_encode($this->users_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function saveReplies() {
        file_put_contents($this->replies_file, json_encode($this->replies_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function saveTabsIndex() {
        usort($this->tabs_index, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        file_put_contents($this->tabs_index_file, json_encode($this->tabs_index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function saveFilesIndex() {
        file_put_contents($this->files_index_file, json_encode($this->files_index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function getTabFilePath($tab_id, $deleted = false) {
        $dir = $deleted ? $this->deleted_tabs_dir : $this->active_tabs_dir;
        return $dir . 'tab_' . $tab_id . '.json';
    }
    
    private function loadTabData($tab_id, $deleted = false) {
        $file = $this->getTabFilePath($tab_id, $deleted);
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }
    
    private function saveTabData($tab_id, $data, $deleted = false) {
        $file = $this->getTabFilePath($tab_id, $deleted);
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    private function createAdmin() {
        $client_id = $this->getClientID();
        $this->users_data[] = [
            'id' => 1,
            'username' => 'admin',
            'fullname' => 'Главный Администратор',
            //Пароль для администратора можно поменять на 'admin'
            'password' => md5('1'),
            'role' => 'admin',
            'client_id' => $client_id,
            'permissions' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'edit_own' => false,
                'delete' => true,
                'delete_own' => false,
                'comment' => true,
                'comment_own' => false,
                'change_status' => true,
                'change_status_own' => false,
                'delete_comment' => true,
                'delete_comment_own' => false,
                'create_tab' => true,
                'create_tab_own' => false,
                'view_tab_users' => true,
                'view_tab_users_own' => false,
                'manage_tab_users' => true,
                'manage_tab_users_own' => false,
                'view_files' => true,
                'view_files_own' => false,
                'upload_files' => true,
                'upload_files_own' => false,
                'delete_files' => true,
                'delete_files_own' => false
            ],
            'tab_permissions' => [],
            'created_at' => date('d.m.Y H:i')
        ];
        $this->saveUsers();
    }
    
    private function createDefaultTabs() {
        $client_id = $this->getClientID();
        
        $tab1 = [
            'id' => 1,
            'name' => 'Все записи',
            'type' => 'all',
            'color' => '#E9ECEF',
            'created_at' => date('d.m.Y H:i'),
            'created_by' => 'admin',
            'created_by_id' => 1,
            'is_default' => true,
            'deleted' => false,
            'order' => 0,
            'permissions' => [
                'add' => false,
                'edit' => true,
                'delete' => false
            ]
        ];
        
        $tab2 = [
            'id' => 2,
            'name' => 'Корзина',
            'type' => 'trash',
            'color' => '#F8D7DA',
            'created_at' => date('d.m.Y H:i'),
            'created_by' => 'admin',
            'created_by_id' => 1,
            'is_default' => true,
            'deleted' => false,
            'order' => 999,
            'permissions' => [
                'add' => false,
                'edit' => false,
                'delete' => false
            ]
        ];
        
        $this->tabs_index = [$tab1, $tab2];
        $this->saveTabsIndex();
        
        $this->saveTabData(1, []);
        $this->saveTabData(2, []);
    }
    
    private function getClientIP() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    public function getClientID() {
        return substr(md5($this->getClientIP()), 0, 8);
    }
    
    public function getUserFullname($username) {
        foreach ($this->users_data as $user) {
            if ($user['username'] === $username) {
                return $user['fullname'] ?? $username;
            }
        }
        return $username;
    }
    
    public function getUserById($user_id) {
        foreach ($this->users_data as $user) {
            if ($user['id'] == $user_id) {
                return $user;
            }
        }
        return null;
    }
    
    public function getUserByUsername($username) {
        foreach ($this->users_data as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }
    
    public function bindClientToUser($username, $client_id) {
        foreach ($this->users_data as &$user) {
            if ($user['username'] === $username) {
                $user['client_id'] = $client_id;
                $this->saveUsers();
                return true;
            }
        }
        return false;
    }
    
    public function checkPassword($username, $password) {
        foreach ($this->users_data as $user) {
            if ($user['username'] === $username && $user['password'] === md5($password)) {
                return $user;
            }
        }
        return false;
    }
    
    public function changePassword($user_id, $old_password, $new_password) {
        foreach ($this->users_data as &$user) {
            if ($user['id'] == $user_id) {
                if ($user['password'] !== md5($old_password)) {
                    return false;
                }
                $user['password'] = md5($new_password);
                $this->saveUsers();
                return true;
            }
        }
        return false;
    }
    
    public function getAllUsers() {
        return $this->users_data;
    }
    
    public function getDefaultTitles() {
        return $this->default_titles;
    }
    
    public function getDefaultObjects() {
        return $this->default_objects;
    }
    
    public function getAllTitles() {
        $all_titles = $this->default_titles;
        foreach ($this->tabs_index as $tab) {
            if ($tab['deleted']) continue;
            $tab_data = $this->loadTabData($tab['id']);
            foreach ($tab_data as $item) {
                if (!in_array($item['title'], $all_titles)) {
                    $all_titles[] = $item['title'];
                }
            }
        }
        sort($all_titles);
        return $all_titles;
    }
    
    public function getAllSystems() {
        $systems = [];
        foreach ($this->tabs_index as $tab) {
            if ($tab['deleted']) continue;
            $tab_data = $this->loadTabData($tab['id']);
            foreach ($tab_data as $item) {
                if (!isset($item['deleted_at']) && !empty($item['system'])) {
                    $systems[] = $item['system'];
                }
            }
        }
        $systems = array_unique($systems);
        sort($systems);
        return $systems;
    }
    
    public function getAllObjects() {
        $objects = [];
        foreach ($this->tabs_index as $tab) {
            if ($tab['deleted']) continue;
            $tab_data = $this->loadTabData($tab['id']);
            foreach ($tab_data as $item) {
                if (!isset($item['deleted_at']) && !empty($item['object'])) {
                    $objects[] = $item['object'];
                }
            }
        }
        $objects = array_unique($objects);
        sort($objects);
        return $objects;
    }
    
    public function deleteCustomTitle($title) {
        if (in_array($title, $this->default_titles)) {
            return false;
        }
        return true;
    }
    
    public function deleteCustomSystem($system) {
        return true;
    }
    
    public function deleteCustomObject($object) {
        if (in_array($object, $this->default_objects)) {
            return false;
        }
        return true;
    }
    
    public function getUsersWithTabAccess($tab_id) {
        $users = [];
        $tab = $this->getTab($tab_id);
        
        foreach ($this->users_data as $user) {
            if ($user['role'] == 'admin') {
                $users[] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'fullname' => $user['fullname'] ?? $user['username'],
                    'role' => $user['role'],
                    'access' => 'full'
                ];
            } else {
                $tab_permissions = $user['tab_permissions'] ?? [];
                if (in_array($tab_id, $tab_permissions)) {
                    $users[] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'fullname' => $user['fullname'] ?? $user['username'],
                        'role' => $user['role'],
                        'access' => 'granted'
                    ];
                }
            }
        }
        return $users;
    }
    
    public function getUsersWithoutTabAccess($tab_id) {
        $users = [];
        $tab = $this->getTab($tab_id);
        
        foreach ($this->users_data as $user) {
            if ($user['role'] == 'admin') continue;
            
            $tab_permissions = $user['tab_permissions'] ?? [];
            if (!in_array($tab_id, $tab_permissions)) {
                $users[] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'fullname' => $user['fullname'] ?? $user['username'],
                    'role' => $user['role']
                ];
            }
        }
        return $users;
    }
    
    public function grantTabAccess($tab_id, $user_id) {
        foreach ($this->users_data as &$user) {
            if ($user['id'] == $user_id && $user['role'] != 'admin') {
                if (!isset($user['tab_permissions'])) {
                    $user['tab_permissions'] = [];
                }
                if (!in_array($tab_id, $user['tab_permissions'])) {
                    $user['tab_permissions'][] = $tab_id;
                    $this->saveUsers();
                }
                return true;
            }
        }
        return false;
    }
    
    public function revokeTabAccess($tab_id, $user_id) {
        foreach ($this->users_data as &$user) {
            if ($user['id'] == $user_id && $user['role'] != 'admin') {
                if (isset($user['tab_permissions'])) {
                    $key = array_search($tab_id, $user['tab_permissions']);
                    if ($key !== false) {
                        array_splice($user['tab_permissions'], $key, 1);
                        $this->saveUsers();
                    }
                }
                return true;
            }
        }
        return false;
    }
    
    public function addUser($username, $fullname, $password, $permissions, $tab_permissions = []) {
        foreach ($this->users_data as $user) {
            if ($user['username'] === $username) {
                return false;
            }
        }
        
        $id = 1;
        if (!empty($this->users_data)) {
            $id = max(array_column($this->users_data, 'id')) + 1;
        }
        
        $this->users_data[] = [
            'id' => $id,
            'username' => $username,
            'fullname' => $fullname ?: $username,
            'password' => md5($password),
            'role' => 'user',
            'permissions' => $permissions,
            'tab_permissions' => $tab_permissions,
            'client_id' => null,
            'created_at' => date('d.m.Y H:i')
        ];
        
        $this->saveUsers();
        return $id;
    }
    
    public function updateUser($id, $username, $fullname, $password, $permissions, $tab_permissions = null) {
        foreach ($this->users_data as &$user) {
            if ($user['id'] == $id) {
                $user['username'] = $username;
                $user['fullname'] = $fullname ?: $username;
                if (!empty($password)) {
                    $user['password'] = md5($password);
                }
                $user['permissions'] = $permissions;
                if ($tab_permissions !== null) {
                    $user['tab_permissions'] = $tab_permissions;
                }
                $this->saveUsers();
                return true;
            }
        }
        return false;
    }
    
    public function deleteUser($id) {
        foreach ($this->users_data as $k => $user) {
            if ($user['id'] == $id && $user['username'] !== 'admin') {
                array_splice($this->users_data, $k, 1);
                $this->saveUsers();
                return true;
            }
        }
        return false;
    }
    
    public function checkPermission($user, $action, $item_author_id = null) {
        if (!$user) return false;
        if ($user['role'] === 'admin') return true;
        
        $perms = $user['permissions'] ?? [];
        
        if ($action === 'view') return $perms['view'] ?? false;
        if ($action === 'add') return $perms['add'] ?? false;
        
        if ($action === 'comment') {
            if (!($perms['comment'] ?? false)) return false;
            if ($perms['comment_own'] ?? false) {
                return $item_author_id && $item_author_id == $user['id'];
            }
            return true;
        }
        
        if ($action === 'change_status') {
            if (!($perms['change_status'] ?? false)) return false;
            if ($perms['change_status_own'] ?? false) {
                return $item_author_id && $item_author_id == $user['id'];
            }
            return true;
        }
        
        if ($action === 'create_tab') {
            return $perms['create_tab'] ?? false;
        }
        
        if ($action === 'view_tab_users') {
            if (!($perms['view_tab_users'] ?? false)) return false;
            return true;
        }
        
        if ($action === 'manage_tab_users') {
            return $perms['manage_tab_users'] ?? false;
        }
        
        if ($action === 'view_files') {
            if (!($perms['view_files'] ?? false)) return false;
            if ($perms['view_files_own'] ?? false) {
                return $item_author_id && $item_author_id == $user['id'];
            }
            return true;
        }
        
        if ($action === 'upload_files') {
            if (!($perms['upload_files'] ?? false)) return false;
            if ($perms['upload_files_own'] ?? false) {
                if ($item_author_id === null) return true;
                return $item_author_id == $user['id'];
            }
            return true;
        }
        
        if ($action === 'delete_files') {
            if (!($perms['delete_files'] ?? false)) return false;
            if ($perms['delete_files_own'] ?? false) {
                return $item_author_id && $item_author_id == $user['id'];
            }
            return true;
        }
        
        if ($action === 'edit') {
            if (!($perms['edit'] ?? false)) return false;
            if ($perms['edit_own'] ?? false) {
                return $item_author_id && $item_author_id == $user['id'];
            }
            return true;
        }
        
        if ($action === 'delete') {
            if (!($perms['delete'] ?? false)) return false;
            if ($perms['delete_own'] ?? false) {
                return $item_author_id && $item_author_id == $user['id'];
            }
            return true;
        }
        
        if ($action === 'delete_comment') {
            if (!($perms['delete_comment'] ?? false)) return false;
            if ($perms['delete_comment_own'] ?? false) {
                return $item_author_id && $item_author_id == $user['id'];
            }
            return true;
        }
        
        return false;
    }
    
    public function checkTabPermission($user, $tab) {
        if (!$user) return false;
        if ($user['role'] === 'admin') return true;
        
        if (isset($tab['created_by_id']) && $tab['created_by_id'] == $user['id']) {
            return true;
        }
        
        $tab_permissions = $user['tab_permissions'] ?? [];
        return in_array($tab['id'], $tab_permissions);
    }
    
    public function getUserTabs($user) {
        if (!$user) return [];
        if ($user['role'] === 'admin') {
            return array_filter($this->tabs_index, function($tab) {
                return !($tab['deleted'] ?? false);
            });
        }
        
        $tabs = [];
        $tab_permissions = $user['tab_permissions'] ?? [];
        
        foreach ($this->tabs_index as $tab) {
            if ($tab['deleted'] ?? false) continue;
            
            if (in_array($tab['id'], $tab_permissions) || 
                (isset($tab['created_by_id']) && $tab['created_by_id'] == $user['id'])) {
                $tabs[] = $tab;
            }
        }
        
        usort($tabs, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        return $tabs;
    }
    
    public function getDeletedTabs($user) {
        if (!$user || $user['role'] !== 'admin') return [];
        
        $deleted = [];
        foreach ($this->tabs_index as $tab) {
            if (($tab['deleted'] ?? false) && !($tab['is_default'] ?? false)) {
                $deleted[] = $tab;
            }
        }
        
        usort($deleted, function($a, $b) {
            $timeA = isset($a['deleted_at']) ? DateTime::createFromFormat('d.m.Y H:i', $a['deleted_at'])->getTimestamp() : 0;
            $timeB = isset($b['deleted_at']) ? DateTime::createFromFormat('d.m.Y H:i', $b['deleted_at'])->getTimestamp() : 0;
            return $timeB - $timeA;
        });
        
        return $deleted;
    }
    
    public function getCustomTabs() {
        $custom = [];
        foreach ($this->tabs_index as $tab) {
            if ($tab['type'] === 'custom' && !($tab['is_default'] ?? false) && !($tab['deleted'] ?? false)) {
                $custom[] = $tab;
            }
        }
        return $custom;
    }
    
    public function getTabs() {
        return array_filter($this->tabs_index, function($tab) {
            return !($tab['deleted'] ?? false);
        });
    }
    
    public function getColors() {
        return $this->colors;
    }
    
    public function addTab($name, $color, $permissions = null) {
        $id = 1;
        if (!empty($this->tabs_index)) {
            $ids = array_column($this->tabs_index, 'id');
            $id = !empty($ids) ? max($ids) + 1 : 1;
        }
        
        $max_order = 0;
        foreach ($this->tabs_index as $tab) {
            if (!($tab['deleted'] ?? false) && ($tab['order'] ?? 0) > $max_order && $tab['type'] !== 'trash' && $tab['type'] !== 'all') {
                $max_order = $tab['order'] ?? 0;
            }
        }
        
        if ($permissions === null) {
            $permissions = [
                'add' => true,
                'edit' => true,
                'delete' => true
            ];
        }
        
        $user = $_SESSION['user'] ?? null;
        $username = $user ? $user['username'] : 'admin';
        $user_id = $user ? $user['id'] : 1;
        
        if ($user && !$this->checkPermission($user, 'create_tab')) {
            return false;
        }
        
        $tab = [
            'id' => $id,
            'name' => $name,
            'type' => 'custom',
            'color' => $color,
            'created_at' => date('d.m.Y H:i'),
            'created_by' => $username,
            'created_by_id' => $user_id,
            'is_default' => false,
            'deleted' => false,
            'order' => $max_order + 10,
            'permissions' => $permissions
        ];
        
        $this->tabs_index[] = $tab;
        $this->saveTabsIndex();
        
        if ($user && $user['role'] != 'admin') {
            $this->grantTabAccess($id, $user_id);
        }
        
        $this->saveTabData($id, []);
        
        return $id;
    }
    
    public function updateTab($id, $name, $color = null, $permissions = null, $order = null) {
        foreach ($this->tabs_index as &$tab) {
            if ($tab['id'] == $id && !$tab['is_default']) {
                $tab['name'] = $name;
                if ($color !== null) {
                    $tab['color'] = $color;
                }
                if ($permissions !== null) {
                    $tab['permissions'] = $permissions;
                }
                if ($order !== null) {
                    $tab['order'] = $order;
                }
                $this->saveTabsIndex();
                return true;
            }
        }
        return false;
    }
    
    public function deleteTab($id) {
        foreach ($this->tabs_index as &$tab) {
            if ($tab['id'] == $id && !($tab['is_default'] ?? false)) {
                $tab['deleted'] = true;
                $tab['deleted_at'] = date('d.m.Y H:i');
                $tab['deleted_by'] = $_SESSION['user']['username'] ?? 'unknown';
                $tab['deleted_by_id'] = $_SESSION['user']['id'] ?? null;
                $tab['deleted_by_fullname'] = isset($_SESSION['user']) ? 
                    ($_SESSION['user']['fullname'] ?? $_SESSION['user']['username']) : 'неизвестно';
                
                $active_file = $this->getTabFilePath($id, false);
                $deleted_file = $this->getTabFilePath($id, true);
                if (file_exists($active_file)) {
                    rename($active_file, $deleted_file);
                }
                
                $this->saveTabsIndex();
                return true;
            }
        }
        return false;
    }
    
    public function restoreTab($id) {
        foreach ($this->tabs_index as &$tab) {
            if ($tab['id'] == $id && ($tab['deleted'] ?? false)) {
                $tab['deleted'] = false;
                unset($tab['deleted_at']);
                unset($tab['deleted_by']);
                unset($tab['deleted_by_id']);
                unset($tab['deleted_by_fullname']);
                
                $deleted_file = $this->getTabFilePath($id, true);
                $active_file = $this->getTabFilePath($id, false);
                if (file_exists($deleted_file)) {
                    rename($deleted_file, $active_file);
                }
                
                $this->saveTabsIndex();
                return true;
            }
        }
        return false;
    }
    
    public function updateTabPermissions($id, $permissions) {
        foreach ($this->tabs_index as &$tab) {
            if ($tab['id'] == $id && $tab['type'] === 'custom') {
                $tab['permissions'] = $permissions;
                $this->saveTabsIndex();
                return true;
            }
        }
        return false;
    }
    
    public function reorderTabs($order) {
        foreach ($this->tabs_index as &$tab) {
            if (isset($order[$tab['id']])) {
                $tab['order'] = $order[$tab['id']];
            }
        }
        $this->saveTabsIndex();
        return true;
    }
    
    public function getTab($tab_id) {
        foreach ($this->tabs_index as $tab) {
            if ($tab['id'] == $tab_id) {
                return $tab;
            }
        }
        return null;
    }
    // Объекты версия 2
    public function getItemsByTab($tab_id, $user = null, $filters = []) {
        $tab = $this->getTab($tab_id);
        
        if (!$tab) return [];
        
        if ($tab['type'] === 'trash') {
            $items = [];
            foreach ($this->tabs_index as $t) {
                if ($t['deleted'] || $t['type'] === 'trash' || $t['type'] === 'all') continue;
                $tab_data = $this->loadTabData($t['id']);
                foreach ($tab_data as $item) {
                    if (isset($item['deleted_at'])) {
                        if ($user && $user['role'] === 'admin') {
                            $items[] = $item;
                        } elseif ($user && isset($item['author_id']) && $item['author_id'] == $user['id']) {
                            $items[] = $item;
                        }
                    }
                }
            }
            
            usort($items, function($a, $b) {
                $timeA = isset($a['deleted_at']) ? DateTime::createFromFormat('d.m.Y H:i', $a['deleted_at'])->getTimestamp() : 0;
                $timeB = isset($b['deleted_at']) ? DateTime::createFromFormat('d.m.Y H:i', $b['deleted_at'])->getTimestamp() : 0;
                if ($timeB != $timeA) {
                    return $timeB - $timeA;
                }
                return ($b['id'] ?? 0) - ($a['id'] ?? 0);
            });
            
            return $items;
        }
        
        if ($tab['deleted']) return [];
        
        if ($user && !$this->checkTabPermission($user, $tab)) {
            return [];
        }
        
        $items = [];
        if ($tab['type'] === 'all') {
            foreach ($this->tabs_index as $t) {
                if ($t['deleted'] || $t['type'] === 'trash' || $t['type'] === 'all') continue;
                if ($user && !$this->checkTabPermission($user, $t)) continue;
                
                $tab_data = $this->loadTabData($t['id']);
                foreach ($tab_data as $item) {
                    if (!isset($item['deleted_at'])) {
                        $items[] = $item;
                    }
                }
            }
        } else {
            $items = $this->loadTabData($tab_id);
            $items = array_filter($items, function($item) {
                return !isset($item['deleted_at']);
            });
        }
        
        // ПРИМЕНЯЕМ ВСЕ ФИЛЬТРЫ
        if (!empty($filters)) {
            $items = array_filter($items, function($item) use ($filters, $user) {
                // Фильтр по автору
                if (isset($filters['author'])) {
                    if ($filters['author'] === 'mine' && (!isset($item['author_id']) || $item['author_id'] != $user['id'])) return false;
                    if ($filters['author'] === 'others' && isset($item['author_id']) && $item['author_id'] == $user['id']) return false;
                }
                
                // Фильтр по заголовку
                if (isset($filters['title']) && $filters['title'] !== 'all' && (!isset($item['title']) || $item['title'] !== $filters['title'])) return false;
                
                // Фильтр по системе
                if (isset($filters['system']) && $filters['system'] !== 'all') {
                    $item_system = isset($item['system']) ? $item['system'] : '';
                    if ($item_system !== $filters['system']) return false;
                }
                
                // Фильтр по объекту
                if (isset($filters['object']) && $filters['object'] !== 'all') {
                    $item_object = isset($item['object']) ? $item['object'] : '';
                    if ($item_object !== $filters['object']) return false;
                }
                
                // Фильтр по поиску в описании
                if (isset($filters['search']) && !empty($filters['search'])) {
                    if (!isset($item['description']) || stripos($item['description'], $filters['search']) === false) return false;
                }
                
                // Фильтр по статусу прочтения
                if (isset($filters['read_status']) && $filters['read_status'] !== 'all') {
                    $is_unread = $this->isUnread($item, $user['id']);
                    if ($filters['read_status'] === 'unread' && !$is_unread) return false;
                    if ($filters['read_status'] === 'read' && $is_unread) return false;
                }
                
                // ИСПРАВЛЕННЫЙ ФИЛЬТР ПО ФАЙЛАМ
                if (isset($filters['files'])) {
                    // Передаем tab_id для проверки принадлежности файла к этой вкладке
                    $has_files = !empty($this->getItemFiles($item['id'], $item['tab_id']));
                    if ($filters['files'] === 'with_files' && !$has_files) return false;
                    if ($filters['files'] === 'without_files' && $has_files) return false;
                }
                
                // Фильтр по статусу (актуально/выполнено)
                if (isset($filters['status']) && $filters['status'] !== 'all') {
                    if ($filters['status'] === 'actual') {
                        if (!($item['is_actual'] ?? true) || ($item['is_completed'] ?? false)) return false;
                    }
                    if ($filters['status'] === 'not_actual') {
                        if (($item['is_actual'] ?? true) || ($item['is_completed'] ?? false)) return false;
                    }
                    if ($filters['status'] === 'completed') {
                        if (!($item['is_completed'] ?? false)) return false;
                    }
                }
                
                return true;
            });
        }
        
        // Сортировка
        $items = array_values($items);
        usort($items, function($a, $b) {
            $timeA = DateTime::createFromFormat('d.m.Y H:i', $a['created'])->getTimestamp();
            $timeB = DateTime::createFromFormat('d.m.Y H:i', $b['created'])->getTimestamp();
            
            if ($timeB != $timeA) {
                return $timeB - $timeA;
            }
            
            return ($b['id'] ?? 0) - ($a['id'] ?? 0);
        });
        
        return $items;
    }
    
    public function getTabName($tab_id) {
        foreach ($this->tabs_index as $tab) {
            if ($tab['id'] == $tab_id) {
                return $tab['name'];
            }
        }
        return 'Неизвестно';
    }
    
    public function getTabColor($tab_id) {
        foreach ($this->tabs_index as $tab) {
            if ($tab['id'] == $tab_id) {
                return $tab['color'] ?? '#E9ECEF';
            }
        }
        return '#E9ECEF';
    }
    
    public function getTitles($tab_id = null, $user = null) {
        $items = $this->getItemsByTab($tab_id ?? 1, $user);
        $titles = array_merge($this->default_titles, array_column($items, 'title'));
        $titles = array_unique($titles);
        sort($titles);
        return $titles;
    }
    
    public function add($title, $desc, $color, $tab_id = 1, $system = '', $object = '') {
        $tab = $this->getTab($tab_id);
        
        if (!$tab) return false;
        if ($tab['type'] === 'all' || $tab['type'] === 'trash') return false;
        if ($tab['deleted']) return false;
        
        $tab_data = $this->loadTabData($tab_id);
        
        $id = 1;
        if (!empty($tab_data)) {
            $ids = array_column($tab_data, 'id');
            $id = !empty($ids) ? max($ids) + 1 : 1;
        }
        
        $user = $_SESSION['user'] ?? null;
        $username = $user ? $user['username'] : 'guest';
        $fullname = $user ? ($user['fullname'] ?? $username) : 'Гость';
        $user_id = $user ? $user['id'] : 0;
        $client_id = $this->getClientID();
        
        $item = [
            'id' => $id,
            'tab_id' => $tab_id,
            'tab_name' => $tab['name'],
            'title' => $title,
            'system' => $system,
            'object' => $object,
            'description' => $desc,
            'color' => $color,
            'created' => date('d.m.Y H:i'),
            'updated' => null,
            'updated_by' => null,
            'updated_by_fullname' => null,
            'updated_by_id' => null,
            'author' => $username,
            'author_fullname' => $fullname,
            'author_id' => $user_id,
            'author_client_id' => $client_id,
            'is_actual' => true,
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
            'completed_by_fullname' => null,
            'completed_by_id' => null,
            'order' => count($tab_data),
            'views' => [],
            'file_views' => [],
            'last_modified' => time()
        ];
        
        $tab_data[] = $item;
        $this->saveTabData($tab_id, $tab_data);
        
        // Сохраняем настройки пользователя
        if ($user) {
            $this->updateUserSettings($user['id'], $title, $system, $object, $color);
        }
        
        return $id;
    }
    
    public function update($id, $title, $desc, $color, $tab_id, $system = '', $object = '') {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return false;
        
        $tab_data = $this->loadTabData($tab_id);
        
        foreach ($tab_data as &$item) {
            if ($item['id'] == $id) {
                $item['title'] = $title;
                $item['system'] = $system;
                $item['object'] = $object;
                $item['description'] = $desc;
                $item['color'] = $color;
                $item['updated'] = date('d.m.Y H:i');
                $item['updated_by'] = $_SESSION['user']['username'] ?? null;
                $item['updated_by_fullname'] = isset($_SESSION['user']) ? ($_SESSION['user']['fullname'] ?? $_SESSION['user']['username']) : null;
                $item['updated_by_id'] = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
                $item['last_modified'] = time();
                $item['views'] = [];
                $item['file_views'] = [];
                
                $this->saveTabData($tab_id, $tab_data);
                
                if (isset($_SESSION['user'])) {
                    $this->updateUserSettings($_SESSION['user']['id'], $title, $system, $object, $color);
                }
                
                return true;
            }
        }
        return false;
    }
    
    public function toggleActual($id, $tab_id) {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return false;
        
        $tab_data = $this->loadTabData($tab_id);
        
        foreach ($tab_data as &$item) {
            if ($item['id'] == $id) {
                $item['is_actual'] = !$item['is_actual'];
                $item['updated'] = date('d.m.Y H:i');
                $item['updated_by'] = $_SESSION['user']['username'] ?? null;
                $item['updated_by_fullname'] = isset($_SESSION['user']) ? ($_SESSION['user']['fullname'] ?? $_SESSION['user']['username']) : null;
                $item['updated_by_id'] = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
                $item['last_modified'] = time();
                $item['views'] = [];
                $this->saveTabData($tab_id, $tab_data);
                return $item['is_actual'];
            }
        }
        return false;
    }
    
    public function toggleCompleted($id, $tab_id) {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return false;
        
        $tab_data = $this->loadTabData($tab_id);
        
        foreach ($tab_data as &$item) {
            if ($item['id'] == $id) {
                $item['is_completed'] = !$item['is_completed'];
                $item['completed_at'] = $item['is_completed'] ? date('d.m.Y H:i') : null;
                $item['completed_by'] = $item['is_completed'] ? ($_SESSION['user']['username'] ?? null) : null;
                $item['completed_by_fullname'] = $item['is_completed'] && isset($_SESSION['user']) ? ($_SESSION['user']['fullname'] ?? $_SESSION['user']['username']) : null;
                $item['completed_by_id'] = $item['is_completed'] && isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
                $item['updated'] = date('d.m.Y H:i');
                $item['updated_by'] = $_SESSION['user']['username'] ?? null;
                $item['updated_by_fullname'] = isset($_SESSION['user']) ? ($_SESSION['user']['fullname'] ?? $_SESSION['user']['username']) : null;
                $item['updated_by_id'] = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
                $item['last_modified'] = time();
                $item['views'] = [];
                $this->saveTabData($tab_id, $tab_data);
                return $item['is_completed'];
            }
        }
        return false;
    }
    
    public function delete($id, $tab_id) {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return false;
        
        $tab_data = $this->loadTabData($tab_id);
        
        foreach ($tab_data as $k => $item) {
            if ($item['id'] == $id) {
                $item['deleted_at'] = date('d.m.Y H:i');
                $item['deleted_by'] = $_SESSION['user']['username'] ?? 'guest';
                $item['deleted_by_fullname'] = isset($_SESSION['user']) ? ($_SESSION['user']['fullname'] ?? $_SESSION['user']['username']) : 'Гость';
                $item['deleted_by_id'] = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
                $item['original_order'] = $item['order'] ?? $k;
                
                $tab_data[$k] = $item;
                $this->saveTabData($tab_id, $tab_data);
                return true;
            }
        }
        return false;
    }
    
    public function restore($id, $tab_id, $as_new = false) {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return false;
        
        $tab_data = $this->loadTabData($tab_id);
        
        foreach ($tab_data as $k => $item) {
            if ($item['id'] == $id && isset($item['deleted_at'])) {
                unset($item['deleted_at']);
                unset($item['deleted_by']);
                unset($item['deleted_by_fullname']);
                unset($item['deleted_by_id']);
                unset($item['original_order']);
                
                $item['updated'] = date('d.m.Y H:i');
                $item['updated_by'] = $_SESSION['user']['username'] ?? null;
                $item['updated_by_fullname'] = isset($_SESSION['user']) ? ($_SESSION['user']['fullname'] ?? $_SESSION['user']['username']) : null;
                $item['updated_by_id'] = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
                $item['last_modified'] = time();
                $item['views'] = [];
                
                if ($as_new) {
                    array_splice($tab_data, $k, 1);
                    $tab_data[] = $item;
                } else {
                    $tab_data[$k] = $item;
                }
                
                $this->saveTabData($tab_id, $tab_data);
                return true;
            }
        }
        return false;
    }
    
    public function get($id, $tab_id) {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return null;
        
        $tab_data = $this->loadTabData($tab_id);
        foreach ($tab_data as $item) {
            if ($item['id'] == $id) return $item;
        }
        return null;
    }
    
    public function markAsRead($item_id, $user_id, $tab_id) {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return false;
        
        $tab_data = $this->loadTabData($tab_id);
        
        foreach ($tab_data as &$item) {
            if ($item['id'] == $item_id) {
                if (!isset($item['views'])) {
                    $item['views'] = [];
                }
                if (!in_array($user_id, $item['views'])) {
                    $item['views'][] = $user_id;
                    $this->saveTabData($tab_id, $tab_data);
                }
                return true;
            }
        }
        return false;
    }
    
    public function markFileAsRead($item_id, $user_id, $tab_id) {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return false;
        
        $tab_data = $this->loadTabData($tab_id);
        
        foreach ($tab_data as &$item) {
            if ($item['id'] == $item_id) {
                if (!isset($item['file_views'])) {
                    $item['file_views'] = [];
                }
                if (!in_array($user_id, $item['file_views'])) {
                    $item['file_views'][] = $user_id;
                    $this->saveTabData($tab_id, $tab_data);
                }
                return true;
            }
        }
        return false;
    }
    
    public function markAllAsRead($user_id, $tab_id = null) {
        $count = 0;
        foreach ($this->tabs_index as $tab) {
            if ($tab['deleted']) continue;
            if ($tab_id !== null && $tab['id'] != $tab_id) continue;
            
            $tab_data = $this->loadTabData($tab['id']);
            $changed = false;
            
            foreach ($tab_data as &$item) {
                if (!isset($item['deleted_at']) && (!isset($item['views']) || !in_array($user_id, $item['views']))) {
                    if (!isset($item['views'])) {
                        $item['views'] = [];
                    }
                    $item['views'][] = $user_id;
                    $changed = true;
                    $count++;
                }
            }
            
            if ($changed) {
                $this->saveTabData($tab['id'], $tab_data);
            }
        }
        return $count;
    }
    
    public function isUnread($item, $user_id) {
        if (!isset($item['views'])) return true;
        return !in_array($user_id, $item['views']);
    }
    
    public function hasNewFiles($item, $user_id) {
        // Передаем tab_id для проверки принадлежности файла к этой вкладке
        $has_files = !empty($this->getItemFiles($item['id'], $item['tab_id']));
        if (!isset($item['file_views'])) return $has_files;
        return $has_files && !in_array($user_id, $item['file_views']);
    }
    
    public function getUnreadCount($user_id, $tab_id = null) {
        $count = 0;
        foreach ($this->tabs_index as $tab) {
            if ($tab['deleted']) continue;
            if ($tab_id !== null && $tab['id'] != $tab_id) continue;
            
            $tab_data = $this->loadTabData($tab['id']);
            foreach ($tab_data as $item) {
                if (!isset($item['deleted_at']) && $this->isUnread($item, $user_id)) {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    public function getUnreadCountByTab($user_id) {
        $unread_counts = [];
        foreach ($this->tabs_index as $tab) {
            if ($tab['deleted']) continue;
            
            $count = 0;
            $tab_data = $this->loadTabData($tab['id']);
            foreach ($tab_data as $item) {
                if (!isset($item['deleted_at']) && $this->isUnread($item, $user_id)) {
                    $count++;
                }
            }
            if ($count > 0) {
                $unread_counts[$tab['id']] = $count;
            }
        }
        return $unread_counts;
    }
    
    public function getFilesCountByTab($user_id) {
        $files_counts = [];
        foreach ($this->tabs_index as $tab) {
            if ($tab['deleted']) continue;
            
            $count = 0;
            $tab_data = $this->loadTabData($tab['id']);
            foreach ($tab_data as $item) {
                if (!isset($item['deleted_at'])) {
                    // ИСПРАВЛЕНО: передаем tab_id
                    $files = $this->getItemFiles($item['id'], $item['tab_id']);
                    if (!empty($files)) {
                        $count++;
                    }
                }
            }
            if ($count > 0) {
                $files_counts[$tab['id']] = $count;
            }
        }
        return $files_counts;
    }
    
    public function addFile($item_id, $tab_id, $file, $user) {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return false;
        
        $item = $this->get($item_id, $tab_id);
        if (!$item) return false;
        
        if (!$this->checkPermission($user, 'upload_files', $item['author_id'] ?? null)) {
            return false;
        }
        
        $user_dir = $this->uploads_dir . $user['id'] . '/' . date('Y/m/d');
        if (!file_exists($user_dir)) {
            mkdir($user_dir, 0777, true);
        }
        
        $original_name = $file['name'];
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        $safe_ext = $this->getSafeExtension($ext);
        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $safe_ext;
        $filepath = $user_dir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return false;
        }
        
        $file_id = 1;
        if (!empty($this->files_index)) {
            $file_id = max(array_column($this->files_index, 'id')) + 1;
        }
        
        $file_info = [
            'id' => $file_id,
            'item_id' => $item_id,
            'tab_id' => $tab_id,
            'filename' => $filename,
            'original_name' => $original_name,
            'filepath' => $filepath,
            'filesize' => $file['size'],
            'mime_type' => $file['type'],
            'is_image' => strpos($file['type'], 'image/') === 0,
            'created_at' => date('d.m.Y H:i'),
            'created_by' => $user['username'],
            'created_by_id' => $user['id'],
            'created_by_fullname' => $user['fullname'] ?? $user['username'],
            'deleted_at' => null,
            'deleted_by' => null
        ];
        
        $this->files_index[] = $file_info;
        $this->saveFilesIndex();
        
        $tab_data = $this->loadTabData($tab_id);
        foreach ($tab_data as &$it) {
            if ($it['id'] == $item_id) {
                $it['last_modified'] = time();
                $it['file_views'] = [];
                $this->saveTabData($tab_id, $tab_data);
                break;
            }
        }
        
        return $file_id;
    }
    
    private function getSafeExtension($ext) {
        $dangerous = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'php-s', 'pht', 'phar', 'htaccess', 'ini', 'sh', 'pl', 'py', 'cgi'];
        $ext = strtolower($ext);
        if (in_array($ext, $dangerous)) {
            return $ext . '.break';
        }
        return $ext;
    }
    
    public function getOriginalExtension($filename) {
        if (strpos($filename, '.break') !== false) {
            return str_replace('.break', '', $filename);
        }
        return $filename;
    }
    
    public function getItemFiles($item_id, $tab_id = null) {
        // Если tab_id не указан, но мы знаем, что должны его использовать,
        // можно добавить предупреждение в лог
        if ($tab_id === null) {
            error_log("Warning: getItemFiles called without tab_id for item: " . $item_id);
        }
        
        return array_filter($this->files_index, function($file) use ($item_id, $tab_id) {
            // Проверяем, что файл принадлежит этой записи
            $belongs_to_item = $file['item_id'] == $item_id;
            
            // Если указан tab_id, проверяем и его
            if ($tab_id !== null) {
                $belongs_to_item = $belongs_to_item && $file['tab_id'] == $tab_id;
            }
            
            return $belongs_to_item && !isset($file['deleted_at']);
        });
    }
    
    public function getFile($file_id) {
        foreach ($this->files_index as $file) {
            if ($file['id'] == $file_id) {
                return $file;
            }
        }
        return null;
    }
    
    public function deleteFile($file_id, $user) {
        foreach ($this->files_index as &$file) {
            if ($file['id'] == $file_id && !isset($file['deleted_at'])) {
                if (!$this->checkPermission($user, 'delete_files', $file['created_by_id'] ?? null)) {
                    return false;
                }
                
                $file['deleted_at'] = date('d.m.Y H:i');
                $file['deleted_by'] = $user['username'];
                $file['deleted_by_id'] = $user['id'];
                $this->saveFilesIndex();
                return true;
            }
        }
        return false;
    }
    
    public function getDeletedFiles($item_id = null, $tab_id = null) {
        return array_filter($this->files_index, function($file) use ($item_id, $tab_id) {
            $deleted = isset($file['deleted_at']);
            if ($item_id !== null && $tab_id !== null) {
                return $deleted && $file['item_id'] == $item_id && $file['tab_id'] == $tab_id;
            } elseif ($item_id !== null) {
                return $deleted && $file['item_id'] == $item_id;
            }
            return $deleted;
        });
    }
    
    public function restoreFile($file_id, $user) {
        foreach ($this->files_index as &$file) {
            if ($file['id'] == $file_id && isset($file['deleted_at'])) {
                if ($user['role'] != 'admin' && $file['created_by_id'] != $user['id']) {
                    return false;
                }
                
                unset($file['deleted_at']);
                unset($file['deleted_by']);
                unset($file['deleted_by_id']);
                $this->saveFilesIndex();
                return true;
            }
        }
        return false;
    }
    
    public function getReplies($item_id) {
        return array_reverse(array_filter($this->replies_data, function($reply) use ($item_id) {
            return $reply['item_id'] == $item_id && !isset($reply['deleted_at']);
        }));
    }
    
    public function getLastReply($item_id) {
        $replies = $this->getReplies($item_id);
        return !empty($replies) ? $replies[0] : null;
    }
    
    public function addReply($item_id, $content, $tab_id) {
        $tab = $this->getTab($tab_id);
        if (!$tab || $tab['deleted']) return false;
        
        $id = 1;
        if (!empty($this->replies_data)) {
            $ids = array_column($this->replies_data, 'id');
            $id = !empty($ids) ? max($ids) + 1 : 1;
        }
        
        $user = $_SESSION['user'] ?? null;
        $username = $user ? $user['username'] : 'guest';
        $fullname = $user ? ($user['fullname'] ?? $username) : 'Гость';
        $user_id = $user ? $user['id'] : 0;
        $client_id = $this->getClientID();
        
        $this->replies_data[] = [
            'id' => $id,
            'item_id' => $item_id,
            'tab_id' => $tab_id,
            'content' => $content,
            'created_at' => date('d.m.Y H:i'),
            'author' => $username,
            'author_fullname' => $fullname,
            'author_id' => $user_id,
            'author_client_id' => $client_id
        ];
        
        $tab_data = $this->loadTabData($tab_id);
        foreach ($tab_data as &$item) {
            if ($item['id'] == $item_id) {
                $item['last_modified'] = time();
                $item['views'] = [];
                $this->saveTabData($tab_id, $tab_data);
                break;
            }
        }
        
        $this->saveReplies();
        return $id;
    }
    
    public function deleteReply($id) {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return false;
        
        foreach ($this->replies_data as $k => $reply) {
            if ($reply['id'] == $id) {
                if ($this->checkPermission($user, 'delete_comment', $reply['author_id'] ?? null)) {
                    $reply['deleted_at'] = date('d.m.Y H:i');
                    $reply['deleted_by'] = $user['username'];
                    $reply['deleted_by_fullname'] = $user['fullname'] ?? $user['username'];
                    $reply['deleted_by_id'] = $user['id'];
                    $this->replies_data[$k] = $reply;
                    $this->saveReplies();
                    return true;
                }
            }
        }
        return false;
    }
    
    // ========== НОВЫЕ МЕТОДЫ ДЛЯ НАСТРОЕК ПОЛЬЗОВАТЕЛЯ ==========
    private function getUserSettingsFile($user_id) {
        return $this->user_settings_dir . 'user_' . $user_id . '.json';
    }
    
    public function loadUserSettings($user_id) {
        $file = $this->getUserSettingsFile($user_id);
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [
            'last_title' => 'Сообщение',
            'last_system' => '',
            'last_object' => '',
            'last_color' => '#FFFFFF'
        ];
    }
    
    private function saveUserSettings($user_id, $settings) {
        $file = $this->getUserSettingsFile($user_id);
        file_put_contents($file, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    public function updateUserSettings($user_id, $title, $system, $object, $color) {
        $settings = $this->loadUserSettings($user_id);
        $settings['last_title'] = $title;
        $settings['last_system'] = $system;
        $settings['last_object'] = $object;
        $settings['last_color'] = $color;
        $this->saveUserSettings($user_id, $settings);
    }
}

// ========== ОБРАБОТКА ЗАПРОСОВ ==========

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = new Storage();

// ========== Обработка загрузки файлов ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
    header('Content-Type: application/json');
    
    $current_user = $_SESSION['user'] ?? null;
    if (!$current_user) {
        echo json_encode(['ok' => false, 'error' => 'Не авторизован']);
        exit;
    }
    
    $item_id = $_POST['item_id'] ?? null;
    $tab_id = $_POST['tab_id'] ?? null;
    
    if (!$item_id || !$tab_id || !isset($_FILES['file'])) {
        echo json_encode(['ok' => false, 'error' => 'Недостаточно данных']);
        exit;
    }
    
    if ($_FILES['file']['size'] > 500 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'error' => 'Файл слишком большой (макс. 500MB)']);
        exit;
    }
    
    $file_id = $db->addFile($item_id, $tab_id, $_FILES['file'], $current_user);
    
    if ($file_id) {
        echo json_encode(['ok' => true, 'file_id' => $file_id]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Ошибка при сохранении файла']);
    }
    exit;
}

// ========== Обработка скачивания/просмотра файлов ==========
if (isset($_GET['download_file']) || isset($_GET['preview_file'])) {
    $current_user = $_SESSION['user'] ?? null;
    if (!$current_user) {
        die('Не авторизован');
    }
    
    $file_id = isset($_GET['download_file']) ? (int)$_GET['download_file'] : (int)$_GET['preview_file'];
    $preview = isset($_GET['preview_file']);
    
    $file = $db->getFile($file_id);
    if (!$file || isset($file['deleted_at'])) {
        die('Файл не найден или удален');
    }
    
    $item = $db->get($file['item_id'], $file['tab_id']);
    if (!$item) {
        die('Запись не найдена');
    }
    
    if (!$db->checkPermission($current_user, 'view_files', $item['author_id'] ?? null)) {
        die('Нет прав на просмотр файла');
    }
    
    $filepath = $file['filepath'];
    if (!file_exists($filepath)) {
        die('Файл не найден на сервере');
    }
    
    if ($preview && $file['is_image']) {
        // Определяем MIME-тип по расширению, если finfo не работает
        $extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml'
        ];
        
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filepath);
            finfo_close($finfo);
        } else {
            $mime = $mime_types[$extension] ?? 'application/octet-stream';
        }
        
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: public, max-age=86400');
        readfile($filepath);
        exit;
    }
    
    $original_name = $file['original_name'];
    $ext = pathinfo($original_name, PATHINFO_EXTENSION);
    $safe_ext = $db->getOriginalExtension($file['filename']);
    if (pathinfo($original_name, PATHINFO_EXTENSION) !== $safe_ext) {
        $original_name = pathinfo($original_name, PATHINFO_FILENAME) . '.' . $safe_ext;
    }
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($original_name) . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    readfile($filepath);
    exit;
}

// ========== HTML ЭКСПОРТ ==========
if (isset($_GET['export_html'])) {
    $tab_id = isset($_GET['tab']) ? (int)$_GET['tab'] : 1;
    $user = $_SESSION['user'] ?? null;
    
    // ПОЛУЧАЕМ ВСЕ ФИЛЬТРЫ ИЗ URL
    $filters = [
        'author' => isset($_GET['author']) ? $_GET['author'] : 'all',
        'title' => isset($_GET['title']) ? $_GET['title'] : 'all',
        'system' => isset($_GET['system']) ? $_GET['system'] : 'all',
        'object' => isset($_GET['object']) ? $_GET['object'] : 'all',
        'search' => isset($_GET['search']) ? $_GET['search'] : '',
        'status' => isset($_GET['status']) ? $_GET['status'] : 'all',
        'read_status' => isset($_GET['read_status']) ? $_GET['read_status'] : 'all',
        'files' => isset($_GET['files']) ? $_GET['files'] : 'all'
    ];
    
    // Получаем записи с применением фильтров
    $items = $db->getItemsByTab($tab_id, $user, $filters);
    
    // ОТЛАДКА - проверим, сколько записей получено и какие фильтры применены
    if ($user['role'] == 'admin') {
        error_log("HTML Export - Tab: $tab_id, Filters: " . print_r($filters, true));
        error_log("HTML Export - Items count: " . count($items));
    }
    
    $current_tab_name = 'Все записи';
    $tabs = $db->getTabs();
    foreach ($tabs as $tab) {
        if ($tab['id'] == $tab_id) {
            $current_tab_name = $tab['name'];
            break;
        }
    }
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="export.html"');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Экспорт - <?=htmlspecialchars($current_tab_name)?></title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 30px; 
            background: white; 
            color: #212529;
            font-size: 14px;
        }
        .header { 
            margin-bottom: 30px; 
            padding-bottom: 20px; 
            border-bottom: 2px solid #343a40;
        }
        h1 { 
            color: #212529; 
            font-size: 24px; 
            margin-bottom: 5px; 
        }
        .info { 
            color: #6c757d; 
            font-size: 14px; 
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th { 
            background: #343a40; 
            color: white; 
            padding: 12px; 
            text-align: left; 
            font-weight: 500;
        }
        td { 
            border: 1px solid #dee2e6; 
            padding: 10px; 
        }
        tr:hover td { 
            background: #f8f9fa; 
        }
        .badge { 
            background: #e9ecef; 
            padding: 2px 10px; 
            border-radius: 20px; 
            font-size: 12px; 
        }
        .footer { 
            margin-top: 30px; 
            color: #6c757d; 
            font-size: 12px; 
            text-align: center; 
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 <?=htmlspecialchars($current_tab_name)?></h1>
        <div class="info">
            📅 Дата экспорта: <?=date('d.m.Y H:i:s')?> • 
            👤 <?=htmlspecialchars($_SESSION['user']['fullname'] ?? $_SESSION['user']['username'] ?? 'guest')?> • 
            🖥️ <?=htmlspecialchars($db->getClientID())?> • 
            📊 Всего записей: <?=count($items)?>
            <?php if (!empty($filters['author']) && $filters['author'] != 'all'): ?> • Фильтр: <?=$filters['author']=='mine'?'Только мои':'Чужие'?><?php endif; ?>
            <?php if (!empty($filters['title']) && $filters['title'] != 'all'): ?> • Заголовок: <?=htmlspecialchars($filters['title'])?><?php endif; ?>
            <?php if (!empty($filters['system']) && $filters['system'] != 'all'): ?> • Система: <?=htmlspecialchars($filters['system'])?><?php endif; ?>
            <?php if (!empty($filters['object']) && $filters['object'] != 'all'): ?> • Объект: <?=htmlspecialchars($filters['object'])?><?php endif; ?>
            <?php if (!empty($filters['search'])): ?> • Поиск: "<?=htmlspecialchars($filters['search'])?>"<?php endif; ?>
            <?php if (!empty($filters['status']) && $filters['status'] != 'all'): ?> • Статус: <?=$filters['status']=='actual'?'Актуальные':($filters['status']=='not_actual'?'Не актуальные':'Выполненные')?><?php endif; ?>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 12%;">Вкладка</th>
                <th style="width: 15%;">Заголовок</th>
                <th style="width: 10%;">Система</th>
                <th style="width: 10%;">Объект</th>
                <th style="width: 20%;">Описание</th>
                <th style="width: 12%;">Автор / ID</th>
                <th style="width: 8%;">Статус</th>
                <th style="width: 8%;">Дата</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
            <tr>
                <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                    Нет записей, соответствующих выбранным фильтрам
                </td>
            </tr>
            <?php else: ?>
                <?php $i = 1; foreach ($items as $item): ?>
                <tr>
                    <td style="text-align: center;"><?=$i++?></td>
                    <td><span class="badge"><?=htmlspecialchars($db->getTabName($item['tab_id'] ?? 1))?></span></td>
                    <td><strong><?=htmlspecialchars($item['title'] ?? '')?></strong></td>
                    <td><?=htmlspecialchars($item['system'] ?? '')?></td>
                    <td><?=htmlspecialchars($item['object'] ?? '')?></td>
                    <td><?=nl2br(htmlspecialchars($item['description'] ?? ''))?></td>
                    <td>
                        <span style="font-weight: 600;"><?=htmlspecialchars($item['author_fullname'] ?? $item['author'] ?? 'Гость')?></span>
                        <br>
                        <span style="color: #17a2b8; font-size: 11px;">ID: <?=htmlspecialchars($item['author_id'] ?? '')?></span>
                    </td>
                    <td>
                        <?php if (isset($item['is_completed']) && $item['is_completed']): ?>
                            ✅ Выполнено
                        <?php elseif (isset($item['is_actual']) && $item['is_actual']): ?>
                            ⭐ Актуально
                        <?php else: ?>
                            ⏳ Не актуально
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 12px;">
                        📅 <?=$item['created'] ?? ''?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        Всего записей: <?=count($items)?> • Для печати: Ctrl+P
    </div>
</body>
</html>
<?php
    exit;
}

// ========== ПРОВЕРКА АВТОРИЗАЦИИ ==========
$current_user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

$last_username = isset($_COOKIE['last_username']) ? $_COOKIE['last_username'] : '';

if (isset($_POST['login'])) {
    $user = $db->checkPassword($_POST['username'], $_POST['password']);
    if ($user) {
        $_SESSION['user'] = $user;
        $client_id = $db->getClientID();
        $db->bindClientToUser($user['username'], $client_id);
        
        setcookie('last_username', $_POST['username'], time() + 30*24*60*60, '/');
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Неверное имя пользователя или пароль';
        setcookie('last_username', $_POST['username'], time() + 30*24*60*60, '/');
        $last_username = $_POST['username'];
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!$current_user) {
    $client_id = $db->getClientID();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 400px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 28px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .client-id {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 5px;
            font-size: 14px;
            color: #495057;
        }
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus {
            border-color: #667eea;
            outline: none;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .info {
            margin-top: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .demo-credentials {
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 13px;
            color: #495057;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🔐 МЕНЕДЖЕР ЗАДАЧ ГБЛ<br>вход в систему</h1>
        <div class="client-id">
            🖥️ Ваш ID: <?=htmlspecialchars($client_id)?>
        </div>
        <?php if (isset($login_error)): ?>
            <div class="error"><?=$login_error?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Имя пользователя" value="<?=htmlspecialchars($last_username)?>" required autofocus>
            <input type="password" name="password" placeholder="Пароль" value="" required>
            <button type="submit" name="login">Войти</button>
        </form>
        <div class="demo-credentials">
            👤 Администратор: admin / 1<br>
            👤 Обычный пользователь: user / 1 (если создан)
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// ========== ОБРАБОТКА POST ЗАПРОСОВ ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $act = $_POST['action'];
    
    if ($act == 'add') echo json_encode(['ok'=>$db->add($_POST['title'], $_POST['description'], $_POST['color'], $_POST['tab_id'], $_POST['system'] ?? '', $_POST['object'] ?? '')]);
    if ($act == 'update') echo json_encode(['ok'=>$db->update($_POST['id'], $_POST['title'], $_POST['description'], $_POST['color'], $_POST['tab_id'], $_POST['system'] ?? '', $_POST['object'] ?? '')]);
    if ($act == 'delete') echo json_encode(['ok'=>$db->delete($_POST['id'], $_POST['tab_id'])]);
    if ($act == 'restore') {
        $as_new = isset($_POST['as_new']) && $_POST['as_new'] == 'true';
        echo json_encode(['ok'=>$db->restore($_POST['id'], $_POST['tab_id'], $as_new)]);
    }
    if ($act == 'get') echo json_encode($db->get($_POST['id'], $_POST['tab_id']));
    if ($act == 'getTitles') echo json_encode($db->getTitles($_POST['tab_id'], $current_user));
    if ($act == 'getAllTitles') echo json_encode($db->getAllTitles());
    if ($act == 'getAllSystems') echo json_encode($db->getAllSystems());
    if ($act == 'getAllObjects') echo json_encode($db->getAllObjects());
    if ($act == 'deleteCustomTitle') echo json_encode(['ok'=>$db->deleteCustomTitle($_POST['title'])]);
    if ($act == 'deleteCustomSystem') echo json_encode(['ok'=>$db->deleteCustomSystem($_POST['system'])]);
    if ($act == 'deleteCustomObject') echo json_encode(['ok'=>$db->deleteCustomObject($_POST['object'])]);
    if ($act == 'toggleActual') echo json_encode(['ok'=>$db->toggleActual($_POST['id'], $_POST['tab_id'])]);
    if ($act == 'toggleCompleted') echo json_encode(['ok'=>$db->toggleCompleted($_POST['id'], $_POST['tab_id'])]);
    if ($act == 'markAsRead') echo json_encode(['ok'=>$db->markAsRead($_POST['item_id'], $current_user['id'], $_POST['tab_id'])]);
    if ($act == 'markFileAsRead') echo json_encode(['ok'=>$db->markFileAsRead($_POST['item_id'], $current_user['id'], $_POST['tab_id'])]);
    if ($act == 'markAllAsRead') {
        $tab_id = isset($_POST['tab_id']) ? $_POST['tab_id'] : null;
        echo json_encode(['count'=>$db->markAllAsRead($current_user['id'], $tab_id)]);
    }
    if ($act == 'getUnreadCount') {
        $tab_id = isset($_POST['tab_id']) ? $_POST['tab_id'] : null;
        echo json_encode(['count'=>$db->getUnreadCount($current_user['id'], $tab_id)]);
    }
    if ($act == 'getTabs') echo json_encode($db->getTabs());
    if ($act == 'getCustomTabs') echo json_encode($db->getCustomTabs());
    if ($act == 'getDeletedTabs') echo json_encode($db->getDeletedTabs($current_user));
    if ($act == 'getColors') echo json_encode($db->getColors());
    if ($act == 'addTab') {
        $permissions = isset($_POST['permissions']) ? json_decode($_POST['permissions'], true) : null;
        echo json_encode(['ok'=>$db->addTab($_POST['name'], $_POST['color'], $permissions)]);
    }
    if ($act == 'updateTab') {
        $permissions = isset($_POST['permissions']) ? json_decode($_POST['permissions'], true) : null;
        $order = isset($_POST['order']) ? (int)$_POST['order'] : null;
        $color = isset($_POST['color']) ? $_POST['color'] : null;
        echo json_encode(['ok'=>$db->updateTab($_POST['id'], $_POST['name'], $color, $permissions, $order)]);
    }
    if ($act == 'deleteTab') echo json_encode(['ok'=>$db->deleteTab($_POST['id'])]);
    if ($act == 'restoreTab') echo json_encode(['ok'=>$db->restoreTab($_POST['id'])]);
    if ($act == 'updateTabPermissions') echo json_encode(['ok'=>$db->updateTabPermissions($_POST['id'], json_decode($_POST['permissions'], true))]);
    if ($act == 'reorderTabs') echo json_encode(['ok'=>$db->reorderTabs(json_decode($_POST['order'], true))]);
    
    // Файлы - ИСПРАВЛЕНО: передаем tab_id
    if ($act == 'getItemFiles') {
        // ИСПРАВЛЕНО: передаем tab_id в метод getItemFiles
        $tab_id = $_POST['tab_id'] ?? null;
        $files = array_values($db->getItemFiles($_POST['item_id'], $tab_id));
        $item = $db->get($_POST['item_id'], $_POST['tab_id'] ?? 1);
        if ($item && !$db->checkPermission($current_user, 'view_files', $item['author_id'] ?? null)) {
            $files = [];
        }
        echo json_encode($files);
        exit;
    }
    if ($act == 'getDeletedFiles') {
        $tab_id = $_POST['tab_id'] ?? null;
        $files = array_values($db->getDeletedFiles($_POST['item_id'] ?? null, $tab_id));
        if ($_POST['item_id']) {
            $item = $db->get($_POST['item_id'], $_POST['tab_id'] ?? 1);
            if ($item && !$db->checkPermission($current_user, 'view_files', $item['author_id'] ?? null)) {
                $files = [];
            }
        }
        echo json_encode($files);
        exit;
    }
    if ($act == 'deleteFile') echo json_encode(['ok'=>$db->deleteFile($_POST['file_id'], $current_user)]);
    if ($act == 'restoreFile') echo json_encode(['ok'=>$db->restoreFile($_POST['file_id'], $current_user)]);
    
    // Комментарии
    if ($act == 'getReplies') echo json_encode(array_values($db->getReplies($_POST['item_id'])));
    if ($act == 'addReply') echo json_encode(['ok'=>$db->addReply($_POST['item_id'], $_POST['content'], $_POST['tab_id'])]);
    if ($act == 'deleteReply') echo json_encode(['ok'=>$db->deleteReply($_POST['id'])]);
    
    // Пользователи
    if ($act == 'getUsers') echo json_encode($db->getAllUsers());
    if ($act == 'changePassword') {
        $ok = $db->changePassword($current_user['id'], $_POST['old_password'], $_POST['new_password']);
        echo json_encode(['ok' => $ok]);
        exit;
    }
    if ($act == 'getUsersWithTabAccess') echo json_encode($db->getUsersWithTabAccess($_POST['tab_id']));
    if ($act == 'getUsersWithoutTabAccess') echo json_encode($db->getUsersWithoutTabAccess($_POST['tab_id']));
    if ($act == 'grantTabAccess') echo json_encode(['ok'=>$db->grantTabAccess($_POST['tab_id'], $_POST['user_id'])]);
    if ($act == 'revokeTabAccess') echo json_encode(['ok'=>$db->revokeTabAccess($_POST['tab_id'], $_POST['user_id'])]);
    if ($act == 'addUser') {
        $tab_permissions = isset($_POST['tab_permissions']) ? json_decode($_POST['tab_permissions'], true) : [];
        echo json_encode(['ok'=>$db->addUser($_POST['username'], $_POST['fullname'], $_POST['password'], json_decode($_POST['permissions'], true), $tab_permissions)]);
    }
    if ($act == 'updateUser') {
        $tab_permissions = isset($_POST['tab_permissions']) ? json_decode($_POST['tab_permissions'], true) : null;
        echo json_encode(['ok'=>$db->updateUser($_POST['id'], $_POST['username'], $_POST['fullname'], $_POST['password'], json_decode($_POST['permissions'], true), $tab_permissions)]);
    }
    if ($act == 'deleteUser') echo json_encode(['ok'=>$db->deleteUser($_POST['id'])]);
    
    exit;
}

// ========== ПОЛУЧАЕМ ДАННЫЕ ДЛЯ ОСНОВНОЙ СТРАНИЦЫ ==========
$all_tabs = $db->getTabs();
$user_tabs = $db->getUserTabs($current_user);
$deleted_tabs = $db->getDeletedTabs($current_user);
$current_tab = isset($_GET['tab']) ? (int)$_GET['tab'] : (count($user_tabs) > 0 ? $user_tabs[0]['id'] : 1);

$current_tab_data = $db->getTab($current_tab);

if (!$db->checkTabPermission($current_user, $current_tab_data)) {
    if (count($user_tabs) > 0) {
        header('Location: ?tab=' . $user_tabs[0]['id']);
        exit;
    }
}

$filters = [
    'author' => isset($_GET['author']) ? $_GET['author'] : 'all',
    'title' => isset($_GET['title']) ? $_GET['title'] : 'all',
    'system' => isset($_GET['system']) ? $_GET['system'] : 'all',
    'object' => isset($_GET['object']) ? $_GET['object'] : 'all',
    'search' => isset($_GET['search']) ? $_GET['search'] : '',
    'status' => isset($_GET['status']) ? $_GET['status'] : 'all',
    'read_status' => isset($_GET['read_status']) ? $_GET['read_status'] : 'all',
    'files' => isset($_GET['files']) ? $_GET['files'] : 'all'
];

$items = $db->getItemsByTab($current_tab, $current_user, $filters);
$all_titles = $db->getAllTitles();
$all_systems = $db->getAllSystems();
$all_objects = $db->getAllObjects();

$can_add_to_tab = false;
if ($current_tab_data && $db->checkTabPermission($current_user, $current_tab_data)) {
    $can_add_to_tab = $db->checkPermission($current_user, 'add');
}

$can_view_tab_users = $db->checkPermission($current_user, 'view_tab_users');
$can_manage_tab_users = $db->checkPermission($current_user, 'manage_tab_users');
$can_view_files = $db->checkPermission($current_user, 'view_files');
$can_upload_files = $db->checkPermission($current_user, 'upload_files');
$can_delete_files = $db->checkPermission($current_user, 'delete_files');
$current_client_id = $db->getClientID();
$colors = $db->getColors();
$default_titles = $db->getDefaultTitles();
$default_objects = $db->getDefaultObjects();
$unread_count = $db->getUnreadCount($current_user['id'], $current_tab);
$unread_counts = $db->getUnreadCountByTab($current_user['id']);

// Загружаем настройки пользователя
$user_settings = $db->loadUserSettings($current_user['id']);
$last_title = $user_settings['last_title'] ?? 'Сообщение';
$last_system = $user_settings['last_system'] ?? '';
$last_object = $user_settings['last_object'] ?? '';
$last_color = $user_settings['last_color'] ?? '#FFFFFF';

$can_manage_current_tab = false;
if ($current_user) {
    if ($current_user['role'] == 'admin') {
        $can_manage_current_tab = true;
    }
}

if ($current_user['role'] == 'admin' && isset($_GET['debug'])) {
    echo "<pre>";
    echo "Текущий пользователь:\n";
    echo "Username: " . $current_user['username'] . "\n";
    echo "ID: " . $current_user['id'] . "\n";
    echo "Unread count: " . $unread_count . "\n";
    echo "Permissions:\n";
    print_r($current_user['permissions']);
    echo "Tab permissions:\n";
    print_r($current_user['tab_permissions'] ?? []);
    echo "Фильтры:\n";
    print_r($filters);
    echo "Unread counts by tab:\n";
    print_r($unread_counts);
    echo "Удаленные вкладки:\n";
    print_r($deleted_tabs);
    echo "Настройки пользователя:\n";
    print_r($user_settings);
    echo "</pre>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Менеджер задач</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f8f9fa; 
            font-family: 'Segoe UI', Arial, sans-serif;
            padding-top: 180px;
        }
        
        body, input, textarea, select, button, td, th {
            font-size: 14px;
        }
        
        .header-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 6px 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-bottom: 2px solid #343a40;
            z-index: 1000;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .header-left h1 {
            font-size: 20px;
            color: #212529;
            font-weight: 600;
            margin: 0;
        }
        
        .fullname-badge {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .role-badge {
            background: #ffc107;
            color: #212529;
            padding: 3px 8px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .client-badge {
            background: #17a2b8;
            color: white;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .unread-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .header-right {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .btn { 
            background: #4CAF50; 
            color: white; 
            border: 1px solid #45a049; 
            padding: 4px 10px;
            border-radius: 4px; 
            cursor: pointer; 
            font-weight: 500;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn:hover { 
            background: #45a049;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn.edit { background: #2196F3; border-color: #1e87db; }
        .btn.edit:hover { background: #1e87db; }
        .btn.del { background: #f44336; border-color: #d32f2f; }
        .btn.del:hover { background: #d32f2f; }
        .btn.excel { background: #217346; border-color: #1e5c3a; }
        .btn.excel:hover { background: #1e5c3a; }
        .btn.html { background: #ff9800; border-color: #f57c00; }
        .btn.html:hover { background: #f57c00; }
        .btn.restore { background: #28a745; border-color: #218838; }
        .btn.restore:hover { background: #218838; }
        .btn.logout { background: #dc3545; border-color: #c82333; }
        .btn.logout:hover { background: #c82333; }
        .btn.users { background: #6f42c1; border-color: #5e35b1; }
        .btn.users:hover { background: #5e35b1; }
        .btn.reply { background: #17a2b8; border-color: #138496; }
        .btn.reply:hover { background: #138496; }
        .btn.tab-edit { background: #fd7e14; border-color: #dc6b12; }
        .btn.tab-edit:hover { background: #dc6b12; }
        .btn.tab-move { background: #6c757d; border-color: #5a6268; }
        .btn.tab-move:hover { background: #5a6268; }
        .btn.tab-users { background: #9c27b0; border-color: #7b1fa2; }
        .btn.tab-users:hover { background: #7b1fa2; }
        .btn.tab-manage { background: #00bcd4; border-color: #00acc1; }
        .btn.tab-manage:hover { background: #00acc1; }
        .btn.filter-reset { background: #6c757d; border-color: #5a6268; }
        .btn.filter-reset:hover { background: #5a6268; }
        .btn.mark-read { background: #28a745; border-color: #218838; }
        .btn.mark-read:hover { background: #218838; }
        .btn.files { background: #edebff; border-color: #f57c00; }
        .btn.files:hover { background: #f57c00; }
        .btn.refresh { background: #17a2b8; border-color: #138496; }
        .btn.refresh:hover { background: #138496; }
        .btn.password { background: #ffc107; border-color: #e0a800; color: #212529; }
        .btn.password:hover { background: #e0a800; }
        .btn-sm { 
            padding: 3px 8px; 
            font-size: 12px; 
            border-radius: 3px;
        }
        
        .tabs-container {
            background: white;
            border-radius: 4px;
            padding: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            border-top: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 8px;
        }
        
        .tabs-list {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            flex: 1;
            align-items: center;
        }
        
        .tab-item {
            position: relative;
            padding: 4px 12px;
            border:1px solid #d0b8f1;
            border-radius: 16px;
            color: #212529;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .tab-item:hover {
            filter: brightness(0.95);
        }
        
        .tab-item.active {
            border: 2px solid #343a40;
            font-weight: 600;
        }
        
        .tab-item.trash {
            background: #f8d7da !important;
        }
        
        .tab-item.all {
            background: #e3f2fd !important;
        }

        .tab-item .tab-settings-btn {
            background: none;
            border: none;
            cursor: pointer;
            opacity: 0.7;
            font-size: 14px;
            padding: 2px 4px;
            border-radius: 4px;
            transition: all 0.2s;
            color: #6c757d;
        }

        .tab-item .tab-settings-btn:hover {
            opacity: 1;
            background: rgba(0,0,0,0.05);
            color: #fd7e14;
        }

        .tab-unread-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            background-color: #dc3545;
            color: white;
            font-size: 11px;
            font-weight: 600;
            border-radius: 10px;
            padding: 0 5px;
            margin-left: 5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
        
        .filters-container {
            background: #f8f9fa;
            padding: 6px 0;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 3px;
            background: white;
            padding: 2px 6px;
            border-radius: 16px;
            border: 1px solid #dee2e6;
            height: 28px;
        }
        
        .filter-group:hover {
            border-color: #adb5bd;
        }
        
        .filter-icon {
            color: #6c757d;
            font-size: 12px;
        }
        
        .filter-select, .filter-input {
            border: none;
            outline: none;
            background: transparent;
            padding: 2px 0;
            min-width: 100px;
            font-size: 12px;
            height: 24px;
        }
        
        .filter-input {
            min-width: 150px;
        }
        
        .filter-reset-all {
            background: #dc3545;
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: 16px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            height: 28px;
        }
        
        .filter-reset-all:hover {
            background: #c82333;
        }
        
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 14px;
            font-size: 11px;
            color: #495057;
        }
        
        .filter-badge-remove {
            cursor: pointer;
            color: #dc3545;
            font-weight: bold;
            margin-left: 4px;
            font-size: 14px;
        }
        
        .filter-badge-remove:hover {
            color: #bd2130;
        }
        
        .main-content {
            padding: 10px 15px;
        }
        
        .table-container {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white;
            min-width: 1800px;
        }
        
        .table th { 
            background: #343a40; 
            color: white; 
            padding: 8px;
            text-align: left; 
            font-weight: 500;
            border-right: 1px solid #495057;
            font-size: 13px;
            white-space: nowrap;
            position: relative;
        }
        
        .table th:last-child { border-right: none; }
        
        .table th .filter-indicator {
            display: inline-flex;
            align-items: center;
            margin-left: 6px;
            cursor: pointer;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 1px 4px;
            font-size: 10px;
        }
        
        .table th .filter-indicator:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .table th .filter-indicator.active {
            background: #ffc107;
            color: #212529;
        }
        
        .table td { 
            padding: 8px;
            border: 1px solid #333;
            vertical-align: middle;
        }
        
        .table tr {
            background-color: transparent;
        }
        
        .table tr:hover {
            filter: brightness(0.70);
        }
        
        .unread-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            transition: all 0.2s;
        }
        
        .unread-indicator.unread {
            background-color: #28a745;
            box-shadow: 0 0 5px #28a745;
        }
        
        .unread-indicator.read {
            background-color: #e9ecef;
        }
        
        .completed-row,
        .completed-row td {
            background-color: rgba(40, 167, 69, 0.4) !important;
            color: #212529 !important;
            text-decoration: line-through;
        }
        
        .not-actual-row,
        .not-actual-row td {
            background-color: #d3d3d3 !important;
            opacity: 0.7;
            color: #495057 !important;
        }
        
        .deleted-row,
        .deleted-row td {
            background-color: #f8d7da !important;
        }
        
        .table tr[style*="background-color"] td {
            background-color: inherit !important;
        }
        
        .tab-badge {
            background: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            display: inline-block;
            white-space: nowrap;
        }
        
        .author-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .author-name {
            font-weight: 600;
            color: #0d47a1;
            font-size: 13px;
        }
        
        .author-name.self {
            color: #1e4620;
            background: #c8e6c9;
            padding: 2px 8px;
            border-radius: 14px;
            display: inline-block;
        }
        
        .author-id {
            color: #17a2b8;
            font-size: 11px;
            display: inline-block;
        }
        
        .client-id-badge {
            background: #17a2b8;
            color: white;
            padding: 2px 8px;
            border-radius: 14px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        
        .checkbox {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .checkbox:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .files-indicator {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #ff9800;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 4px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .files-indicator.new {
            background: #28a745;
            animation: pulse 2s infinite;
        }
        
        .reply-badge {
            background: #17a2b8;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 4px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .status-container {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #6c757d;
            white-space: nowrap;
        }
        
        .actions { 
            display: flex; 
            gap: 4px; 
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 2000;
        }
        
        .modal-content { 
            background: white; 
            width: 500px;
            margin: 40px auto;
            padding: 20px;
            border-radius: 8px; 
            border: 2px solid #343a40;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            pointer-events: auto;
        }
        
        .modal-large {
            width: 1200px;
        }
        
        .modal-medium {
            width: 900px;
        }
        
        .modal-small {
            width: 350px;
        }
        
        .title-selector, .system-selector, .object-selector {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .title-selector select, .system-selector select, .object-selector select {
            flex: 1;
            padding: 6px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .delete-title-btn, .delete-system-btn, .delete-object-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 16px;
            padding: 0 5px;
        }
        
        .delete-title-btn:hover, .delete-system-btn:hover, .delete-object-btn:hover {
            color: #bd2130;
        }
        
        .delete-title-btn.disabled, .delete-system-btn.disabled, .delete-object-btn.disabled {
            color: #6c757d;
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .select-all-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .select-all-btn:hover {
            background: #5a6268;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 2px;
            display: block;
            font-size: 13px;
        }
        
        input, textarea, select { 
            width: 100%; 
            padding: 8px;
            margin: 2px 0 10px 0;
            border: 1px solid #dee2e6; 
            border-radius: 4px; 
            font-size: 13px;
        }
        
        textarea { 
            height: 100px; 
            resize: vertical; 
        }
        
        .color-selector {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 6px;
            margin: 10px 0;
        }
        
        .color-option {
            width: 100%;
            aspect-ratio: 1;
            border: 2px solid transparent;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .color-option:hover {
            transform: scale(1.1);
            border-color: #343a40;
        }
        
        .color-option.selected {
            border-color: #343a40;
            transform: scale(1.05);
        }
        
        h3 { 
            margin-bottom: 15px; 
            color: #212529;
            border-bottom: 2px solid #343a40;
            padding-bottom: 8px;
            font-size: 18px;
        }
        
        h4 { 
            margin: 10px 0 5px;
            color: #495057;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        
        .permissions-group {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .permission-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .permission-row:last-child {
            border-bottom: none;
        }
        
        .permission-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .permission-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
        }
        
        .permission-own {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #6c757d;
            margin-left: 15px;
        }
        
        .permission-own input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
        }
        
        .tab-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 6px;
            margin: 10px 0;
            max-height: 250px;
            overflow-y: auto;
            padding: 8px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .tab-checkbox-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px;
        }
        
        .tab-checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
        }
        
        .file-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
        }
        
        .file-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .file-name {
            font-weight: 600;
            color: #2196F3;
            font-size: 13px;
            word-break: break-all;
        }
        
        .file-meta {
            font-size: 11px;
            color: #6c757d;
        }
        
        .file-preview {
            margin-top: 10px;
            text-align: center;
            max-height: 200px;
            overflow: hidden;
        }
        
        .file-preview img {
            max-width: 100%;
            max-height: 200px;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .file-preview img.enlarged {
            max-height: 500px;
        }
        
        .file-actions {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            justify-content: flex-end;
        }
        
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .file-upload-area:hover {
            border-color: #4CAF50;
            background: #f1f9f1;
        }
        
        .file-upload-area.dragover {
            border-color: #4CAF50;
            background: #e8f5e9;
        }
        
        .file-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .reply-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
        }
        
        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .reply-content {
            color: #495057;
            font-size: 13px;
            line-height: 1.4;
            word-wrap: break-word;
        }
        
        .last-reply {
            margin-top: 8px;
            padding: 6px;
            background: #f8f9fa;
            border-left: 3px solid #17a2b8;
            font-size: 12px;
            color: #495057;
            max-height: 70px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .last-reply:hover {
            background: #e9ecef;
        }
        
        .tab-order-controls {
            display: flex;
            gap: 8px;
            margin: 10px 0;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            align-items: center;
        }
        
        .tab-order-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        
        .tab-order-item:hover {
            background: #f1f3f5;
        }
        
        .tab-order-handle {
            cursor: move;
            color: #6c757d;
            font-size: 16px;
        }
        
        .reply-count {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #17a2b8;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            margin-right: 6px;
            cursor: pointer;
        }
        
        .user-list {
            max-height: 350px;
            overflow-y: auto;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .user-name {
            font-weight: 600;
            color: #0d47a1;
        }
        
        .user-role {
            font-size: 11px;
            color: #6c757d;
        }
        
        .access-badge {
            padding: 3px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .access-badge.full {
            background: #4CAF50;
            color: white;
        }
        
        .access-badge.granted {
            background: #2196F3;
            color: white;
        }
        
        .access-badge.none {
            background: #6c757d;
            color: white;
        }
        
        .active-filters {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 5px;
            padding: 3px 0;
        }
        
        @media (max-width: 768px) {
            body { padding-top: 220px; }
            .header-top { flex-direction: column; gap: 5px; }
            .header-left, .header-right { justify-content: center; }
            .header-left { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <div class="header-fixed">
        <div class="header-top">
            <div class="header-left">
                <h1>📋 Менеджер задач</h1>
                <span class="fullname-badge" style="display: flex; align-items: center; gap: 5px;">
                    👤 <?=htmlspecialchars($current_user['fullname'] ?? $current_user['username'])?>
                    <button class="btn password btn-sm" onclick="openPasswordModal()" title="Сменить пароль">🔑</button>
                </span>
                <span class="role-badge">
                    <?=$current_user['role'] == 'admin' ? 'Администратор' : 'Пользователь'?>
                </span>
                <span class="client-badge">
                    🖥️ ID: <?=htmlspecialchars($current_client_id)?>
                </span>
                <?php if ($unread_count > 0): ?>
                <span class="unread-badge">
                    🔔 <?=$unread_count?> новых
                </span>
                <?php endif; ?>
            </div>
            <div class="header-right">
                <button class="btn refresh" onclick="location.reload()" title="Обновить">🔄 Обновить</button>
                
                <?php if ($can_add_to_tab): ?>
                <button class="btn" onclick="openModal()">➕ Добавить</button>
                <?php endif; ?>
                
                <?php if ($current_tab_data && $current_tab_data['type'] !== 'trash'): ?>
                <button class="btn excel" onclick="exportExcel()">📊 Excel</button>
                <button class="btn html" onclick="exportHTML(<?=$current_tab?>)">🌐 HTML</button>
                <?php endif; ?>
                
                <?php if ($db->checkPermission($current_user, 'create_tab')): ?>
                <button class="btn" onclick="addTab()">➕ Вкладка</button>
                <?php endif; ?>
                
                <?php if ($unread_count > 0): ?>
                <button class="btn mark-read" onclick="markAllAsRead()">✅ Прочитать все</button>
                <?php endif; ?>
                
                <?php if ($current_user['role'] == 'admin'): ?>
                <button class="btn users" onclick="showUsers()">👥 Пользователи</button>
                <button class="btn tab-edit" onclick="manageTabs()">📑 Управление</button>
                <button class="btn tab-move" onclick="showDeletedTabs()">🗑️ Корзина вкладок</button>
                <a href="?debug=1" class="btn" style="background: #17a2b8;">🔍 Отладка</a>
                <?php endif; ?>
                
                <a href="?logout=1" class="btn logout">🚪 Выйти</a>
            </div>
        </div>
        
        <div class="tabs-container">
            <div class="tabs-list">
                <?php foreach ($user_tabs as $tab): 
                    $tab_class = 'tab-item';
                    if ($tab['type'] == 'trash') $tab_class .= ' trash';
                    if ($tab['type'] == 'all') $tab_class .= ' all';
                    if ($current_tab == $tab['id']) $tab_class .= ' active';
                    $tab_color = $tab['color'] ?? '#E9ECEF';
                    
                    $can_edit_tab = ($current_user['role'] == 'admin') || 
                                    (isset($tab['created_by_id']) && $tab['created_by_id'] == $current_user['id']);
                    
                    $tab_unread_count = $unread_counts[$tab['id']] ?? 0;
                ?>
                <div class="<?=$tab_class?>" style="background: <?=$tab_color?>; position: relative;">
                    <a href="?tab=<?=$tab['id']?><?=!empty($filters) ? '&' . http_build_query($filters) : ''?>" style="color: #212529; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                        <?php if ($tab['type'] == 'trash'): ?>🗑️<?php endif; ?>
                        <?php if ($tab['type'] == 'all'): ?>📋<?php endif; ?>
                        <?=htmlspecialchars($tab['name'])?>
                        <?php if ($tab['type'] == 'trash'): ?>
                            (<?=count($db->getItemsByTab($tab['id'], $current_user))?>)
                        <?php endif; ?>
                        <?php if ($tab['type'] == 'all'): ?>
                            (<?=count($db->getItemsByTab($tab['id'], $current_user))?>)
                        <?php endif; ?>
                        <?php if ($tab_unread_count > 0): ?>
                            <span class="tab-unread-badge"><?=$tab_unread_count?></span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if (!$tab['is_default'] && $tab['type'] == 'custom' && $can_edit_tab): ?>
                    <button class="tab-settings-btn" onclick="openTabSettings(event, <?=$tab['id']?>, '<?=htmlspecialchars($tab['name'])?>', '<?=$tab['color'] ?? '#E9ECEF'?>', <?=htmlspecialchars(json_encode($tab['permissions'] ?? []), ENT_QUOTES, 'UTF-8')?>)" title="Настройки вкладки">⚙️</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Панель фильтров -->
        <div class="filters-container">
            <div class="filter-group">
                <span class="filter-icon">👤</span>
                <select class="filter-select" id="filterAuthor" onchange="applyFilters()">
                    <option value="all" <?=$filters['author'] == 'all' ? 'selected' : ''?>>Все авторы</option>
                    <option value="mine" <?=$filters['author'] == 'mine' ? 'selected' : ''?>>Только мои</option>
                    <option value="others" <?=$filters['author'] == 'others' ? 'selected' : ''?>>Только чужие</option>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-icon">📌</span>
                <select class="filter-select" id="filterTitle" onchange="applyFilters()">
                    <option value="all" <?=$filters['title'] == 'all' ? 'selected' : ''?>>Все заголовки</option>
                    <?php foreach ($all_titles as $title): ?>
                    <option value="<?=htmlspecialchars($title)?>" <?=$filters['title'] == $title ? 'selected' : ''?>><?=htmlspecialchars($title)?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-icon">⚙️</span>
                <select class="filter-select" id="filterSystem" onchange="applyFilters()">
                    <option value="all" <?=$filters['system'] == 'all' ? 'selected' : ''?>>Все системы</option>
                    <?php foreach ($all_systems as $system): ?>
                    <option value="<?=htmlspecialchars($system)?>" <?=$filters['system'] == $system ? 'selected' : ''?>><?=htmlspecialchars($system)?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-icon">📦</span>
                <select class="filter-select" id="filterObject" onchange="applyFilters()">
                    <option value="all" <?=$filters['object'] == 'all' ? 'selected' : ''?>>Все объекты</option>
                    <?php foreach ($all_objects as $object): ?>
                    <option value="<?=htmlspecialchars($object)?>" <?=$filters['object'] == $object ? 'selected' : ''?>><?=htmlspecialchars($object)?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-icon">🔍</span>
                <input type="text" class="filter-input" id="filterSearch" placeholder="Поиск по описанию..." value="<?=htmlspecialchars($filters['search'])?>" onkeyup="if(event.key==='Enter') applyFilters()">
            </div>

            <div class="filter-group">
                <span class="filter-icon">⚡</span>
                <select class="filter-select" id="filterStatus" onchange="applyFilters()">
                    <option value="all" <?=$filters['status'] == 'all' ? 'selected' : ''?>>Все статусы</option>
                    <option value="actual" <?=$filters['status'] == 'actual' ? 'selected' : ''?>>Актуальные</option>
                    <option value="not_actual" <?=$filters['status'] == 'not_actual' ? 'selected' : ''?>>Не актуальные</option>
                    <option value="completed" <?=$filters['status'] == 'completed' ? 'selected' : ''?>>Выполненные</option>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-icon">👁️</span>
                <select class="filter-select" id="filterReadStatus" onchange="applyFilters()">
                    <option value="all" <?=$filters['read_status'] == 'all' ? 'selected' : ''?>>Все записи</option>
                    <option value="unread" <?=$filters['read_status'] == 'unread' ? 'selected' : ''?>>Непрочитанные</option>
                    <option value="read" <?=$filters['read_status'] == 'read' ? 'selected' : ''?>>Прочитанные</option>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-icon">📎</span>
                <select class="filter-select" id="filterFiles" onchange="applyFilters()">
                    <option value="all" <?=$filters['files'] == 'all' ? 'selected' : ''?>>Все записи</option>
                    <option value="with_files" <?=$filters['files'] == 'with_files' ? 'selected' : ''?>>С файлами</option>
                    <option value="without_files" <?=$filters['files'] == 'without_files' ? 'selected' : ''?>>Без файлов</option>
                </select>
            </div>

            <button class="filter-reset-all" onclick="resetAllFilters()">
                <span>🔄</span> Сброс
            </button>
        </div>

        <!-- Активные фильтры -->
        <?php 
        $activeFilters = [];
        if ($filters['author'] != 'all') $activeFilters[] = ['type' => 'author', 'label' => 'Автор: ' . ($filters['author'] == 'mine' ? 'Только мои' : 'Только чужие')];
        if ($filters['title'] != 'all') $activeFilters[] = ['type' => 'title', 'label' => 'Заголовок: ' . $filters['title']];
        if ($filters['system'] != 'all') $activeFilters[] = ['type' => 'system', 'label' => 'Система: ' . $filters['system']];
        if ($filters['object'] != 'all') $activeFilters[] = ['type' => 'object', 'label' => 'Объект: ' . $filters['object']];
        if (!empty($filters['search'])) $activeFilters[] = ['type' => 'search', 'label' => 'Поиск: "' . $filters['search'] . '"'];
        if ($filters['status'] != 'all') {
            $statusLabels = ['actual' => 'Актуальные', 'not_actual' => 'Не актуальные', 'completed' => 'Выполненные'];
            $activeFilters[] = ['type' => 'status', 'label' => 'Статус: ' . $statusLabels[$filters['status']]];
        }
        if ($filters['read_status'] != 'all') {
            $readLabels = ['unread' => 'Непрочитанные', 'read' => 'Прочитанные'];
            $activeFilters[] = ['type' => 'read_status', 'label' => 'Прочтение: ' . $readLabels[$filters['read_status']]];
        }
        if ($filters['files'] != 'all') {
            $filesLabels = ['with_files' => 'С файлами', 'without_files' => 'Без файлов'];
            $activeFilters[] = ['type' => 'files', 'label' => 'Файлы: ' . $filesLabels[$filters['files']]];
        }
        ?>
        
        <?php if (!empty($activeFilters)): ?>
        <div class="active-filters">
            <?php foreach ($activeFilters as $filter): ?>
            <span class="filter-badge">
                <?=$filter['label']?>
                <span class="filter-badge-remove" onclick="removeFilter('<?=$filter['type']?>')">×</span>
            </span>
            <?php endforeach; ?>
            <span class="filter-badge" style="background: #17a2b8; color: white;">
                Найдено: <?=count($items)?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;">№</th>
                        <?php if ($current_tab_data && $current_tab_data['type'] == 'all'): ?>
                        <th style="width: 100px;">
                            ВКЛАДКА
                            <span class="filter-indicator <?=$filters['title'] != 'all' ? 'active' : ''?>" onclick="showTitleFilter()">📌</span>
                        </th>
                        <?php endif; ?>
                        <th style="width: 150px;">
                            ЗАГОЛОВОК
                            <span class="filter-indicator <?=$filters['title'] != 'all' ? 'active' : ''?>" onclick="showTitleFilter()">📌</span>
                        </th>
                        <th style="width: 120px;">
                            СИСТЕМА
                            <span class="filter-indicator <?=$filters['system'] != 'all' ? 'active' : ''?>" onclick="showSystemFilter()">⚙️</span>
                        </th>
                        <th style="width: 120px;">
                            ОБЪЕКТ
                            <span class="filter-indicator <?=$filters['object'] != 'all' ? 'active' : ''?>" onclick="showObjectFilter()">📦</span>
                        </th>
                        <th>
                            ОПИСАНИЕ
                            <span class="filter-indicator <?=!empty($filters['search']) ? 'active' : ''?>" onclick="focusSearch()">🔍</span>
                        </th>
                        <th style="width: 100px;">
                            ВЛОЖЕНИЯ
                        </th>
                        <th style="width: 180px;">
                            АВТОР / ID
                            <span class="filter-indicator <?=$filters['author'] != 'all' ? 'active' : ''?>" onclick="showAuthorFilter()">👤</span>
                        </th>
                        <th style="width: 250px;">
                            ДАТЫ / СТАТУС
                            <span class="filter-indicator <?=$filters['status'] != 'all' ? 'active' : ''?>" onclick="showStatusFilter()">⚡</span>
                        </th>
                        <th style="width: 140px;">ДЕЙСТВИЯ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$items): ?>
                    <tr>
                        <td colspan="<?=$current_tab_data && $current_tab_data['type'] == 'all' ? 10 : 9?>" style="padding: 0;">
                            <div class="empty-state">
                                <p style="font-size: 16px; margin-bottom: 8px;">
                                    <?php if ($current_tab_data && $current_tab_data['type'] == 'trash'): ?>🗑️ Корзина пуста<?php else: ?>📭 Нет записей<?php endif; ?>
                                </p>
                                <?php if (!empty($activeFilters)): ?>
                                <p style="color: #6c757d; font-size: 13px;">Попробуйте сбросить фильтры</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($items as $item): 
                            $is_self = isset($item['author_id']) && isset($current_user['id']) && $item['author_id'] == $current_user['id'];
                            $is_unread = $db->isUnread($item, $current_user['id']);
                            $has_new_files = $db->hasNewFiles($item, $current_user['id']);
                            $item_files = $db->getItemFiles($item['id'], $item['tab_id']); // ИСПРАВЛЕНО: передаем tab_id
                            $files_count = count($item_files);
                            
                            $row_class = '';
                            if ($current_tab_data && $current_tab_data['type'] !== 'trash') {
                                if (isset($item['is_completed']) && $item['is_completed']) $row_class = 'completed-row';
                                elseif (isset($item['is_actual']) && !$item['is_actual']) $row_class = 'not-actual-row';
                            } else {
                                $row_class = 'deleted-row';
                            }
                            
                            $replies = $db->getReplies($item['id']);
                            $replies_count = count($replies);
                            $last_reply = $db->getLastReply($item['id']);
                            $can_change_status = $db->checkPermission($current_user, 'change_status', $item['author_id'] ?? null);
                            $can_comment = $db->checkPermission($current_user, 'comment', $item['author_id'] ?? null);
                            $can_view_files = $db->checkPermission($current_user, 'view_files', $item['author_id'] ?? null);
                            $can_upload_files = $db->checkPermission($current_user, 'upload_files', $item['author_id'] ?? null);
                            $item_tab_name = $db->getTabName($item['tab_id'] ?? 1);
                            $item_color = $item['color'] ?? '#FFFFFF';
                            
                            $display_title = htmlspecialchars($item['title'] ?? '');
                            $display_system = htmlspecialchars($item['system'] ?? '');
                            $display_object = htmlspecialchars($item['object'] ?? '');
                            
                            $can_edit = false;
                            $can_delete = false;
                            
                            if ($current_user) {
                                if ($current_user['role'] == 'admin') {
                                    $can_edit = true;
                                    $can_delete = true;
                                    $can_change_status = true;
                                    $can_comment = true;
                                    $can_view_files = true;
                                    $can_upload_files = true;
                                } else {
                                    $perms = $current_user['permissions'] ?? [];
                                    
                                    if ($perms['edit'] ?? false) {
                                        if ($perms['edit_own'] ?? false) {
                                            $can_edit = $is_self;
                                        } else {
                                            $can_edit = true;
                                        }
                                    }
                                    
                                    if ($perms['delete'] ?? false) {
                                        if ($perms['delete_own'] ?? false) {
                                            $can_delete = $is_self;
                                        } else {
                                            $can_delete = true;
                                        }
                                    }
                                }
                            }
                        ?>
                        <tr id="row-<?=$item['id']?>" class="<?=$row_class?>" style="background-color: <?=$item_color?>;" onclick="markAsRead(<?=$item['id']?>, <?=$item['tab_id']?>)">
                            <td style="text-align: center;">
                                <span class="unread-indicator <?=$is_unread ? 'unread' : 'read'?>"></span>
                                <?=$i++?>
                            </td>
                            
                            <?php if ($current_tab_data && $current_tab_data['type'] == 'all'): ?>
                            <td>
                                <span class="tab-badge" style="background: <?=$db->getTabColor($item['tab_id'] ?? 1)?>; color: #212529;">
                                    <?=htmlspecialchars($item_tab_name)?>
                                </span>
                            </td>
                            <?php endif; ?>
                            
                            <td>
                                <?=$display_title?>
                            </td>
                            
                            <td>
                                <?=$display_system?>
                            </td>
                            
                            <td>
                                <?=$display_object?>
                            </td>
                            
                            <td>
                                <?=nl2br(htmlspecialchars($item['description'] ?? ''))?>
                                <?php if ($replies_count > 0 || $last_reply): ?>
                                <div class="last-reply" onclick="event.stopPropagation(); openReplyModal(<?=$item['id']?>, '<?=htmlspecialchars($item['title'])?>', <?=$item['tab_id']?>)">
                                    <strong>💬 Комментарии (<?=$replies_count?>):</strong><br>
                                    <?php if ($last_reply): ?>
                                        <?=htmlspecialchars($last_reply['author_fullname'] ?? $last_reply['author'])?>: <?=htmlspecialchars(substr($last_reply['content'], 0, 100))?><?=strlen($last_reply['content']) > 100 ? '...' : ''?>
                                    <?php else: ?>
                                        Нет комментариев. Нажмите чтобы добавить.
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($files_count > 0 && $can_view_files): ?>
                                <span class="files-indicator <?=$has_new_files ? 'new' : ''?>" onclick="event.stopPropagation(); openFilesModal(<?=$item['id']?>, '<?=htmlspecialchars($item['title'])?>', <?=$item['tab_id']?>)">
                                    📎 <?=$files_count?>
                                </span>
                                <?php endif; ?>
                                <?php if ($can_upload_files && $current_tab_data && $current_tab_data['type'] != 'trash'): ?>
                                <button class="btn files btn-sm" onclick="event.stopPropagation(); openFilesUploadModal(<?=$item['id']?>, <?=$item['tab_id']?>)" title="Добавить файлы">📎</button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="author-info">
                                    <span class="author-name <?=$is_self ? 'self' : ''?>">
                                        <?=htmlspecialchars($item['author_fullname'] ?? $item['author'] ?? 'Гость')?>
                                    </span>
                                    <span class="author-id">
                                        🖥️ <?=htmlspecialchars($item['author_client_id'] ?? '')?>
                                    </span>
                                    <?php if (isset($item['deleted_by'])): ?>
                                    <span style="color: #dc3545; font-size: 11px;">
                                        🗑️ <?=htmlspecialchars($item['deleted_by_fullname'] ?? $item['deleted_by'])?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($current_tab_data && $current_tab_data['type'] !== 'trash'): ?>
                                    <div style="margin-bottom: 4px;">
                                        <span style="color: #28a745;">📅 <?=$item['created'] ?? ''?></span>
                                        <?php if (!empty($item['updated'])): ?>
                                            <span style="color: #2196F3; margin-left: 8px;">
                                                ✏️ <?=$item['updated']?>
                                                <?php if (!empty($item['updated_by_fullname'])): ?>
                                                (<?=htmlspecialchars($item['updated_by_fullname'])?>)
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['completed_at'])): ?>
                                            <span style="color: #28a745; margin-left: 8px;">
                                                ✅ <?=$item['completed_at']?>
                                                <?php if (!empty($item['completed_by_fullname'])): ?>
                                                (<?=htmlspecialchars($item['completed_by_fullname'])?>)
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="status-container">
                                        <span class="status-item">
                                            ✓ Выполнено
                                            <input type="checkbox" class="checkbox" 
                                                   onchange="toggleCompleted(<?=$item['id']?>, <?=$item['tab_id']?>, this)"
                                                   <?=isset($item['is_completed']) && $item['is_completed'] ? 'checked' : ''?>
                                                   <?=!$can_change_status ? 'disabled' : ''?>>
                                        </span>
                                        <span class="status-item">
                                            ⭐ Актуально
                                            <input type="checkbox" class="checkbox" 
                                                   onchange="toggleActual(<?=$item['id']?>, <?=$item['tab_id']?>, this)"
                                                   <?=isset($item['is_actual']) && $item['is_actual'] ? 'checked' : ''?>
                                                   <?=!$can_change_status ? 'disabled' : ''?>>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div>📅 <?=$item['created'] ?? ''?></div>
                                    <div style="color: #dc3545;">🗑️ <?=$item['deleted_at'] ?? ''?></div>
                                    <div>👤 <?=htmlspecialchars($item['deleted_by_fullname'] ?? $item['deleted_by'] ?? '')?></div>
                                    <div>🖥️ <?=htmlspecialchars($item['deleted_by_id'] ?? '')?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="actions" onclick="event.stopPropagation()">
                                    <?php if ($current_tab_data && $current_tab_data['type'] !== 'trash'): ?>
                                        <?php if ($can_comment): ?>
                                        <button class="btn reply btn-sm" onclick="openReplyModal(<?=$item['id']?>, '<?=htmlspecialchars($item['title'])?>', <?=$item['tab_id']?>)">
                                            💬
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($can_edit): ?>
                                        <button class="btn edit btn-sm" onclick="editItem(<?=$item['id']?>, <?=$item['tab_id']?>)">
                                            ✏️
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($can_delete): ?>
                                        <button class="btn del btn-sm" onclick="deleteItem(<?=$item['id']?>, <?=$item['tab_id']?>)">
                                            🗑️
                                        </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($current_user['role'] == 'admin' || $is_self): ?>
                                        <button class="btn restore btn-sm" onclick="restoreItem(<?=$item['id']?>, <?=$item['tab_id']?>)">
                                            ♻️
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Модальное окно для смены пароля -->
    <div id="passwordModal" class="modal">
        <div class="modal-content modal-small" onclick="event.stopPropagation()">
            <h3>🔑 Смена пароля</h3>
            <form onsubmit="changePassword(event)">
                <div style="margin-bottom: 15px;">
                    <label class="form-label">Текущий пароль</label>
                    <input type="password" id="oldPassword" required>
                </div>
                <div style="margin-bottom: 15px;">
                    <label class="form-label">Новый пароль</label>
                    <input type="password" id="newPassword" required>
                </div>
                <div style="margin-bottom: 15px;">
                    <label class="form-label">Подтверждение нового пароля</label>
                    <input type="password" id="confirmPassword" required>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn del" onclick="closePasswordModal()" style="background: #6c757d;">❌ Отмена</button>
                    <button type="submit" class="btn">💾 Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для добавления/редактирования записи -->
    <div id="modal" class="modal">
        <div class="modal-content" onclick="event.stopPropagation()">
            <h3 id="modalTitle">➕ Добавить запись</h3>
            <form onsubmit="saveItem(event)">
                <input type="hidden" id="itemId">
                <input type="hidden" id="tabId" value="<?=$current_tab?>">
                <input type="hidden" id="selectedColor" value="#FFFFFF">
                
                <!-- Заголовок -->
                <label class="form-label">Заголовок</label>
                <div class="title-selector">
                    <select id="titleSelect" onchange="onTitleChange()">
                        <?php foreach ($all_titles as $title): ?>
                        <option value="<?=htmlspecialchars($title)?>" <?=$title == $last_title ? 'selected' : ''?>><?=htmlspecialchars($title)?></option>
                        <?php endforeach; ?>
                        <option value="--custom--">✏️ Свой заголовок...</option>
                    </select>
                    <button type="button" class="delete-title-btn" id="deleteTitleBtn" onclick="deleteCustomTitle()" title="Удалить заголовок">🗑️</button>
                </div>
                
                <input type="text" id="title" placeholder="Введите свой заголовок" style="display: none;">
                <!-- Описание -->
                <label class="form-label">Описание</label>
                <textarea id="description" placeholder="Введите описание" required></textarea>
                
                
                
                <!-- Система -->
                <label class="form-label">Система</label>
                <div class="system-selector">
                    <select id="systemSelect" onchange="onSystemChange()">
                        <option value="">— Пусто —</option>
                        <?php foreach ($all_systems as $system): ?>
                        <option value="<?=htmlspecialchars($system)?>" <?=$system == $last_system ? 'selected' : ''?>><?=htmlspecialchars($system)?></option>
                        <?php endforeach; ?>
                        <option value="--custom--">✏️ Своя система...</option>
                    </select>
                    <button type="button" class="delete-system-btn" id="deleteSystemBtn" onclick="deleteCustomSystem()" title="Удалить систему">🗑️</button>
                </div>
                
                <input type="text" id="system" placeholder="Введите свою систему" style="display: none;">
                
                <!-- Объект -->
                <label class="form-label">Объект</label>
                <div class="object-selector">
                    <select id="objectSelect" onchange="onObjectChange()">
                        <option value="">— Пусто —</option>
                        <?php foreach ($default_objects as $object): ?>
                        <option value="<?=htmlspecialchars($object)?>" <?=$object == $last_object ? 'selected' : ''?>><?=htmlspecialchars($object)?></option>
                        <?php endforeach; ?>
                        <?php foreach ($all_objects as $object): ?>
                        <?php if (!in_array($object, $default_objects)): ?>
                        <option value="<?=htmlspecialchars($object)?>" <?=$object == $last_object ? 'selected' : ''?>><?=htmlspecialchars($object)?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <option value="--custom--">✏️ Свой объект...</option>
                    </select>
                    <button type="button" class="delete-object-btn" id="deleteObjectBtn" onclick="deleteCustomObject()" title="Удалить объект">🗑️</button>
                </div>
                
                <input type="text" id="object" placeholder="Введите свой объект" style="display: none;">
                
                
                
                <h4>🎨 Выберите цвет записи</h4>
                <div class="color-selector" id="colorSelector">
                    <?php foreach ($colors as $index => $color): ?>
                    <div class="color-option <?=$index == 0 ? 'selected' : ''?>" 
                         style="background: <?=$color?>;" 
                         onclick="selectColor(this, '<?=$color?>')"
                         data-color="<?=$color?>"></div>
                    <?php endforeach; ?>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn del" onclick="closeModal()" style="background: #6c757d;">❌ Отмена</button>
                    <button type="submit" class="btn">💾 Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для добавления/редактирования вкладки -->
    <div id="tabModal" class="modal">
        <div class="modal-content" onclick="event.stopPropagation()">
            <h3 id="tabModalTitle">➕ Добавить вкладку</h3>
            <form onsubmit="saveTab(event)">
                <input type="hidden" id="tabId">
                <input type="hidden" id="tabColor" value="#FFFFFF">
                
                <input type="text" id="tabName" placeholder="Введите название вкладки" required>
                
                <h4>🎨 Выберите цвет вкладки</h4>
                <div class="color-selector" id="tabColorSelector">
                    <?php foreach ($colors as $index => $color): ?>
                    <div class="color-option <?=$index == 0 ? 'selected' : ''?>" 
                         style="background: <?=$color?>;" 
                         onclick="selectTabColor(this, '<?=$color?>')"
                         data-color="<?=$color?>"></div>
                    <?php endforeach; ?>
                </div>
                
                <div class="permissions-group">
                    <h4 style="font-size: 14px; margin-bottom: 5px;">Права доступа к вкладке</h4>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="tab_perm_add" checked> ➕ Добавление записей
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="tab_perm_edit" checked> ✏️ Редактирование
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="tab_perm_delete" checked> 🗑️ Удаление
                        </span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn del" onclick="closeTabModal()" style="background: #6c757d;">❌ Отмена</button>
                    <button type="submit" class="btn">💾 Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для управления вкладками -->
    <div id="tabManageModal" class="modal">
        <div class="modal-content modal-medium" onclick="event.stopPropagation()">
            <h3>📑 Управление вкладками</h3>
            <div style="margin: 15px 0;">
                <h4 style="margin-bottom: 8px;">Порядок отображения</h4>
                <div id="tabOrderList" style="margin-bottom: 15px;"></div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="saveTabOrder()">💾 Сохранить порядок</button>
                    <button type="button" class="btn del" onclick="closeTabManageModal()" style="background: #6c757d;">❌ Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для управления пользователями -->
    <div id="usersModal" class="modal">
        <div class="modal-content modal-large" onclick="event.stopPropagation()">
            <h3>👥 Управление пользователями</h3>
            <div id="usersList" style="overflow-x: auto;"></div>
            <div style="display: flex; gap: 8px; justify-content: space-between; margin-top: 15px;">
                <button class="btn" onclick="openUserModal()">➕ Добавить пользователя</button>
                <button class="btn del" onclick="closeUsersModal()" style="background: #6c757d;">❌ Закрыть</button>
            </div>
        </div>
    </div>

    <!-- Модальное окно для просмотра пользователей вкладки -->
    <div id="tabUsersModal" class="modal">
        <div class="modal-content modal-medium" onclick="event.stopPropagation()">
            <h3 id="tabUsersModalTitle">👥 Пользователи с доступом к вкладке</h3>
            <div id="tabUsersList" class="user-list" style="margin: 15px 0;"></div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn del" onclick="closeTabUsersModal()" style="background: #6c757d;">❌ Закрыть</button>
            </div>
        </div>
    </div>

    <!-- Модальное окно для управления доступом к вкладке -->
    <div id="tabManageUsersModal" class="modal">
        <div class="modal-content modal-medium" onclick="event.stopPropagation()">
            <h3 id="tabManageUsersModalTitle">🔑 Управление доступом к вкладке</h3>
            <div style="margin: 15px 0;">
                <h4 style="margin-bottom: 8px;">Пользователи с доступом</h4>
                <div id="tabGrantedUsersList" class="user-list" style="margin-bottom: 15px; max-height: 200px;"></div>
                <h4 style="margin-bottom: 8px;">Добавить доступ</h4>
                <div id="tabAvailableUsersList" class="user-list" style="max-height: 200px;"></div>
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn del" onclick="closeTabManageUsersModal()" style="background: #6c757d;">❌ Закрыть</button>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления/редактирования пользователя -->
    <div id="userModal" class="modal">
        <div class="modal-content modal-large" onclick="event.stopPropagation()">
            <h3 id="userModalTitle">➕ Добавить пользователя</h3>
            <div style="margin-bottom: 10px;">
                <button type="button" class="select-all-btn" onclick="selectAllPermissions(true)">✅ Выбрать все</button>
                <button type="button" class="select-all-btn" onclick="selectAllPermissions(false)">❌ Снять все</button>
            </div>
            <form onsubmit="saveUser(event)">
                <input type="hidden" id="userId">
                <label for="fullname">Полное имя (для отображения)</label>
                <input type="text" id="fullname" placeholder="Введите полное имя" required>
                <label for="username">Логин (для входа)</label>
                <input type="text" id="username" placeholder="Введите логин" required>
                <label for="password">Пароль</label>
                <input type="password" id="password" placeholder="Введите пароль (оставьте пустым если не меняете)">
                <div class="permissions-group">
                    <h4 style="font-size: 14px; margin-bottom: 5px;">Глобальные права</h4>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_view" checked> 👁️ Просмотр записей
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_add"> ➕ Добавление записей
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_edit"> ✏️ Редактирование
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_edit_own"> только свои
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_delete"> 🗑️ Удаление
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_delete_own"> только свои
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_comment" checked> 💬 Комментирование
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_comment_own"> только свои
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_change_status"> ⚡ Изменение статусов
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_change_status_own"> только свои
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_create_tab"> ➕ Создание вкладок
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_create_tab_own"> только свои
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_view_tab_users"> 👥 Просмотр пользователей вкладок
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_view_tab_users_own"> только свои
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_manage_tab_users"> 🔑 Управление доступом к вкладкам
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_manage_tab_users_own"> только свои
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_view_files"> 📄 Просмотр файлов
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_view_files_own"> только свои
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_upload_files"> 📎 Загрузка файлов
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_upload_files_own"> только к своим записям
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_delete_files"> 🗑️ Удаление файлов
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_delete_files_own"> только свои
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="perm_delete_comment"> 🗑️ Удаление комментариев
                        </span>
                        <span class="permission-own">
                            <input type="checkbox" id="perm_delete_comment_own"> только свои
                        </span>
                    </div>
                </div>
                <h4 style="margin-top: 10px;">📌 Доступные вкладки</h4>
                <div class="tab-checkbox-grid" id="tabPermissionsContainer">
                    <?php foreach ($all_tabs as $tab): ?>
                    <div class="tab-checkbox-item">
                        <input type="checkbox" class="tab-permission-checkbox" id="tab_perm_<?=$tab['id']?>" value="<?=$tab['id']?>">
                        <label for="tab_perm_<?=$tab['id']?>" style="background: <?=$tab['color'] ?? '#E9ECEF'?>; padding: 3px 6px; border-radius: 12px; font-size: 12px;">
                            <?php if ($tab['type'] == 'all'): ?>📋<?php endif; ?>
                            <?php if ($tab['type'] == 'trash'): ?>🗑️<?php endif; ?>
                            <?=htmlspecialchars($tab['name'])?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 15px;">
                    <button type="button" class="btn del" onclick="closeUserModal()" style="background: #6c757d;">❌ Отмена</button>
                    <button type="submit" class="btn">💾 Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для комментариев -->
    <div id="replyModal" class="modal">
        <div class="modal-content modal-medium" onclick="event.stopPropagation()">
            <h3 id="replyModalTitle">💬 Комментарии</h3>
            <div style="background: #e9ecef; padding: 10px; border-radius: 4px; margin-bottom: 12px; font-size: 13px;">
                <strong>📌 <?=htmlspecialchars($current_user['fullname'] ?? $current_user['username'])?> (<?=htmlspecialchars($current_client_id)?>)</strong><br>
                <span id="replyOriginalTitle"></span>
            </div>
            <form onsubmit="saveReply(event)">
                <input type="hidden" id="replyItemId">
                <input type="hidden" id="replyTabId">
                <textarea id="replyContent" placeholder="Текст комментария" required style="height: 80px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 8px;">
                    <button type="button" class="btn del" onclick="closeReplyModal()" style="background: #6c757d;">❌ Отмена</button>
                    <button type="submit" class="btn reply">💬 Отправить</button>
                </div>
            </form>
            <div id="repliesList" style="margin-top: 15px;">
                <h4 style="font-size: 14px; margin-bottom: 8px;">Все комментарии</h4>
                <div id="repliesContainer"></div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для файлов -->
    <div id="filesModal" class="modal">
        <div class="modal-content modal-medium" onclick="event.stopPropagation()">
            <h3 id="filesModalTitle">📎 Файлы</h3>
            <div style="background: #e9ecef; padding: 10px; border-radius: 4px; margin-bottom: 12px; font-size: 13px;">
                <strong>📌 <?=htmlspecialchars($current_user['fullname'] ?? $current_user['username'])?> (<?=htmlspecialchars($current_client_id)?>)</strong><br>
                <span id="filesOriginalTitle"></span>
            </div>
            <div style="margin-bottom: 15px;">
                <button class="btn files" onclick="showFilesTab('active')">📎 Активные</button>
                <button class="btn del" onclick="showFilesTab('deleted')">🗑️ Корзина</button>
            </div>
            
            <?php 
            // Проверяем, может ли пользователь загружать файлы
            $can_user_upload = $db->checkPermission($current_user, 'upload_files');
            if ($can_user_upload): 
            ?>
            <div id="filesUploadArea" class="file-upload-area" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                <input type="file" id="fileInput" style="display: none;" multiple onchange="handleFileSelect()">
                <p>📎 Перетащите файлы сюда или <a href="#" onclick="document.getElementById('fileInput').click(); return false;">выберите</a></p>
                <p style="font-size: 11px; color: #6c757d;">Макс. размер: 500MB</p>
            </div>
            <?php endif; ?>
            
            <div id="filesList" class="file-list" style="margin-top: 15px;"></div>
            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 15px;">
                <button type="button" class="btn del" onclick="closeFilesModal()" style="background: #6c757d;">❌ Закрыть</button>
            </div>
        </div>
    </div>

    <!-- Модальное окно для настроек вкладки -->
    <div id="tabSettingsModal" class="modal">
        <div class="modal-content" onclick="event.stopPropagation()">
            <h3 id="tabSettingsModalTitle">⚙️ Настройки вкладки</h3>
            <form onsubmit="saveTabSettings(event)">
                <input type="hidden" id="settingsTabId">
                <label for="settingsTabName">Название вкладки</label>
                <input type="text" id="settingsTabName" placeholder="Введите название вкладки" required>
                <h4>🎨 Цвет вкладки</h4>
                <div class="color-selector" id="settingsColorSelector">
                    <?php foreach ($colors as $index => $color): ?>
                    <div class="color-option <?=$index == 0 ? 'selected' : ''?>" 
                         style="background: <?=$color?>;" 
                         onclick="selectSettingsColor(this, '<?=$color?>')"
                         data-color="<?=$color?>"></div>
                    <?php endforeach; ?>
                </div>
                <div class="permissions-group">
                    <h4 style="font-size: 14px; margin-bottom: 5px;">Права доступа к вкладке</h4>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="settings_perm_add"> ➕ Добавление записей
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="settings_perm_edit"> ✏️ Редактирование
                        </span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-label">
                            <input type="checkbox" id="settings_perm_delete"> 🗑️ Удаление
                        </span>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; justify-content: space-between; margin-top: 20px;">
                    <div>
                        <button type="button" class="btn users" onclick="showTabUsersFromSettings()">👥 Просмотр пользователей</button>
                        <button type="button" class="btn tab-manage" onclick="manageTabUsersFromSettings()">🔑 Управление доступом</button>
                    </div>
                    <div>
                        <button type="button" class="btn del" onclick="deleteTabFromSettings()" style="background: #dc3545;">🗑️ Удалить вкладку</button>
                        <button type="button" class="btn del" onclick="closeTabSettingsModal()" style="background: #6c757d;">❌ Отмена</button>
                        <button type="submit" class="btn">💾 Сохранить</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для удаленных вкладок -->
    <div id="deletedTabsModal" class="modal">
        <div class="modal-content modal-medium" onclick="event.stopPropagation()">
            <h3>🗑️ Удаленные вкладки</h3>
            <div id="deletedTabsList" class="user-list" style="margin: 15px 0;"></div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn del" onclick="closeDeletedTabsModal()" style="background: #6c757d;">❌ Закрыть</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        const modal = document.getElementById('modal');
        const tabModal = document.getElementById('tabModal');
        const tabManageModal = document.getElementById('tabManageModal');
        const usersModal = document.getElementById('usersModal');
        const userModal = document.getElementById('userModal');
        const replyModal = document.getElementById('replyModal');
        const filesModal = document.getElementById('filesModal');
        const tabUsersModal = document.getElementById('tabUsersModal');
        const tabManageUsersModal = document.getElementById('tabManageUsersModal');
        const tabSettingsModal = document.getElementById('tabSettingsModal');
        const deletedTabsModal = document.getElementById('deletedTabsModal');
        const passwordModal = document.getElementById('passwordModal');
        let sortableInstance = null;
        let currentColor = '<?=$last_color?>';
        let currentTabColor = '#FFFFFF';
        let currentSettingsTabId = null;
        let currentSettingsTabName = '';
        let currentSettingsTabColor = '#FFFFFF';
        let currentFilesItemId = null;
        let currentFilesTabId = null;
        let currentFilesItemTitle = '';
        let currentFilesTab = 'active';
        
        const defaultTitles = <?=json_encode($default_titles)?>;
        const defaultObjects = <?=json_encode($default_objects)?>;
        
        // Функции для выбора цвета
        function selectColor(element, color) {
            document.querySelectorAll('#colorSelector .color-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selectedColor').value = color;
            currentColor = color;
        }
        
        function selectTabColor(element, color) {
            document.querySelectorAll('#tabColorSelector .color-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('tabColor').value = color;
            currentTabColor = color;
        }
        
        function selectSettingsColor(element, color) {
            document.querySelectorAll('#settingsColorSelector .color-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            currentSettingsTabColor = color;
        }
        
        // Функции для заголовков
        function onTitleChange() {
            const titleSelect = document.getElementById('titleSelect');
            const titleInput = document.getElementById('title');
            const deleteBtn = document.getElementById('deleteTitleBtn');
            
            if (titleSelect.value === '--custom--') {
                titleInput.style.display = 'block';
                titleInput.value = '';
                titleInput.required = true;
                deleteBtn.style.display = 'inline-block';
                deleteBtn.classList.remove('disabled');
                deleteBtn.disabled = false;
            } else {
                titleInput.style.display = 'none';
                titleInput.value = titleSelect.value;
                titleInput.required = false;
                
                if (defaultTitles.includes(titleSelect.value)) {
                    deleteBtn.classList.add('disabled');
                    deleteBtn.disabled = true;
                } else {
                    deleteBtn.classList.remove('disabled');
                    deleteBtn.disabled = false;
                }
            }
        }
        
        // Функции для системы
        function onSystemChange() {
            const systemSelect = document.getElementById('systemSelect');
            const systemInput = document.getElementById('system');
            const deleteBtn = document.getElementById('deleteSystemBtn');
            
            if (systemSelect.value === '--custom--') {
                systemInput.style.display = 'block';
                systemInput.value = '';
                systemInput.required = false;
                deleteBtn.style.display = 'inline-block';
                deleteBtn.classList.remove('disabled');
                deleteBtn.disabled = false;
            } else {
                systemInput.style.display = 'none';
                systemInput.value = systemSelect.value;
                systemInput.required = false;
                
                if (systemSelect.value === '') {
                    deleteBtn.style.display = 'none';
                } else {
                    deleteBtn.style.display = 'inline-block';
                    deleteBtn.classList.remove('disabled');
                    deleteBtn.disabled = false;
                }
            }
        }
        
        // Функции для объекта
        function onObjectChange() {
            const objectSelect = document.getElementById('objectSelect');
            const objectInput = document.getElementById('object');
            const deleteBtn = document.getElementById('deleteObjectBtn');
            
            if (objectSelect.value === '--custom--') {
                objectInput.style.display = 'block';
                objectInput.value = '';
                objectInput.required = false;
                deleteBtn.style.display = 'inline-block';
                deleteBtn.classList.remove('disabled');
                deleteBtn.disabled = false;
            } else {
                objectInput.style.display = 'none';
                objectInput.value = objectSelect.value;
                objectInput.required = false;
                
                if (objectSelect.value === '' || defaultObjects.includes(objectSelect.value)) {
                    deleteBtn.classList.add('disabled');
                    deleteBtn.disabled = true;
                    deleteBtn.style.display = 'inline-block';
                } else {
                    deleteBtn.classList.remove('disabled');
                    deleteBtn.disabled = false;
                    deleteBtn.style.display = 'inline-block';
                }
            }
        }
        
        function loadSystems() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getAllSystems'
            })
            .then(r => r.json())
            .then(systems => {
                const select = document.getElementById('systemSelect');
                const currentVal = select.value;
                while (select.options.length > 2) {
                    select.remove(1);
                }
                systems.forEach(sys => {
                    const option = document.createElement('option');
                    option.value = sys;
                    option.textContent = sys;
                    select.insertBefore(option, select.lastElementChild);
                });
                if (currentVal && currentVal !== '--custom--') {
                    select.value = currentVal;
                }
            });
        }
        
        function loadObjects() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getAllObjects'
            })
            .then(r => r.json())
            .then(objects => {
                const select = document.getElementById('objectSelect');
                const currentVal = select.value;
                
                // Сохраняем опции "Пусто" и "--custom--"
                const emptyOption = select.options[0];
                const customOption = select.options[select.options.length - 1];
                
                // Очищаем все опции кроме первых двух (пусто и --custom--)
                while (select.options.length > 2) {
                    select.remove(1);
                }
                
                // Добавляем стандартные объекты
                defaultObjects.forEach(obj => {
                    if (obj) { // Пропускаем пустую строку, если она есть в defaultObjects
                        const option = document.createElement('option');
                        option.value = obj;
                        option.textContent = obj;
                        select.insertBefore(option, customOption);
                    }
                });
                
                // Добавляем пользовательские объекты (кроме стандартных)
                objects.forEach(obj => {
                    if (!defaultObjects.includes(obj)) {
                        const option = document.createElement('option');
                        option.value = obj;
                        option.textContent = obj;
                        select.insertBefore(option, customOption);
                    }
                });
                
                // Восстанавливаем выбранное значение
                if (currentVal && currentVal !== '--custom--') {
                    // Проверяем, существует ли опция с таким значением
                    let found = false;
                    for (let i = 0; i < select.options.length; i++) {
                        if (select.options[i].value === currentVal) {
                            select.value = currentVal;
                            found = true;
                            break;
                        }
                    }
                    if (!found && currentVal !== '') {
                        select.value = '--custom--';
                        document.getElementById('object').value = currentVal;
                        document.getElementById('object').style.display = 'block';
                    }
                }
                
                // Обновляем видимость input и состояние кнопки удаления
                onObjectChange();
            });
        }
        
        function deleteCustomTitle() {
            const titleSelect = document.getElementById('titleSelect');
            const title = titleSelect.value;
            
            if (defaultTitles.includes(title)) {
                alert('Нельзя удалить стандартный заголовок');
                return;
            }
            
            if (confirm('Удалить заголовок "' + title + '"?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteCustomTitle&title=' + encodeURIComponent(title)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        for (let i = 0; i < titleSelect.options.length; i++) {
                            if (titleSelect.options[i].value === title) {
                                titleSelect.remove(i);
                                break;
                            }
                        }
                        titleSelect.value = 'Сообщение';
                        onTitleChange();
                    }
                });
            }
        }
        
        function deleteCustomSystem() {
            const systemSelect = document.getElementById('systemSelect');
            const system = systemSelect.value;
            
            if (system === '') return;
            
            if (confirm('Удалить систему "' + system + '"?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteCustomSystem&system=' + encodeURIComponent(system)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        for (let i = 0; i < systemSelect.options.length; i++) {
                            if (systemSelect.options[i].value === system) {
                                systemSelect.remove(i);
                                break;
                            }
                        }
                        systemSelect.value = '';
                        onSystemChange();
                    }
                });
            }
        }
        
        function deleteCustomObject() {
            const objectSelect = document.getElementById('objectSelect');
            const object = objectSelect.value;
            
            if (defaultObjects.includes(object)) {
                alert('Нельзя удалить стандартный объект');
                return;
            }
            
            if (confirm('Удалить объект "' + object + '"?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteCustomObject&object=' + encodeURIComponent(object)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        for (let i = 0; i < objectSelect.options.length; i++) {
                            if (objectSelect.options[i].value === object) {
                                objectSelect.remove(i);
                                break;
                            }
                        }
                        objectSelect.value = '';
                        onObjectChange();
                    }
                });
            }
        }
        
        // Функции для фильтров
        function applyFilters() {
            const params = new URLSearchParams(window.location.search);
            params.set('author', document.getElementById('filterAuthor').value);
            params.set('title', document.getElementById('filterTitle').value);
            params.set('system', document.getElementById('filterSystem').value);
            params.set('object', document.getElementById('filterObject').value);
            params.set('search', document.getElementById('filterSearch').value);
            params.set('status', document.getElementById('filterStatus').value);
            params.set('read_status', document.getElementById('filterReadStatus').value);
            params.set('files', document.getElementById('filterFiles').value);
            params.set('tab', <?=$current_tab?>);
            window.location.href = '?' + params.toString();
        }    
        
        function resetAllFilters() {
            const params = new URLSearchParams(window.location.search);
            params.delete('author');
            params.delete('title');
            params.delete('system');
            params.delete('object');
            params.delete('search');
            params.delete('status');
            params.delete('read_status');
            params.delete('files');
            params.set('tab', <?=$current_tab?>);
            window.location.href = '?' + params.toString();
        }
        
        function removeFilter(type) {
            const params = new URLSearchParams(window.location.search);
            params.delete(type);
            params.set('tab', <?=$current_tab?>);
            window.location.href = '?' + params.toString();
        }
        
        function showTitleFilter() { document.getElementById('filterTitle').focus(); }
        function showSystemFilter() { document.getElementById('filterSystem').focus(); }
        function showObjectFilter() { document.getElementById('filterObject').focus(); }
        function showAuthorFilter() { document.getElementById('filterAuthor').focus(); }
        function showStatusFilter() { document.getElementById('filterStatus').focus(); }
        function focusSearch() { document.getElementById('filterSearch').focus(); }
        
        // Функции для отметки прочитанного
        function markAsRead(item_id, tab_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=markAsRead&item_id=' + item_id + '&tab_id=' + tab_id
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    const indicator = document.querySelector(`#row-${item_id} .unread-indicator`);
                    if (indicator) {
                        indicator.classList.remove('unread');
                        indicator.classList.add('read');
                    }
                    updateUnreadCounts();
                }
            });
        }
        
        function markAllAsRead() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=markAllAsRead&tab_id=<?=$current_tab?>'
            })
            .then(r => r.json())
            .then(data => { if (data.count > 0) location.reload(); });
        }
        
        function updateUnreadCounts() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getUnreadCount&tab_id=<?=$current_tab?>'
            })
            .then(r => r.json())
            .then(data => {
                const badge = document.querySelector('.unread-badge');
                if (badge) {
                    if (data.count > 0) {
                        badge.style.display = 'inline-flex';
                        badge.textContent = '🔔 ' + data.count + ' новых';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            });
        }
        
        // Функции для смены пароля
        function openPasswordModal() {
            passwordModal.style.display = 'block';
            document.getElementById('oldPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        }
        
        function closePasswordModal() {
            passwordModal.style.display = 'none';
        }
        
        function changePassword(e) {
            e.preventDefault();
            
            const oldPass = document.getElementById('oldPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (newPass !== confirmPass) {
                alert('Новый пароль и подтверждение не совпадают');
                return;
            }
            
            if (newPass.length < 1) {
                alert('Пароль не может быть пустым');
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=changePassword&old_password=' + encodeURIComponent(oldPass) + '&new_password=' + encodeURIComponent(newPass)
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    alert('Пароль успешно изменен');
                    closePasswordModal();
                } else {
                    alert('Неверный текущий пароль');
                }
            });
        }
        
        // Функции для вкладок
        function addTab() {
            document.getElementById('tabModalTitle').textContent = '➕ Добавить вкладку';
            document.getElementById('tabId').value = '';
            document.getElementById('tabName').value = '';
            document.getElementById('tabColor').value = '#FFFFFF';
            currentTabColor = '#FFFFFF';
            document.querySelectorAll('#tabColorSelector .color-option').forEach((opt, index) => {
                index === 0 ? opt.classList.add('selected') : opt.classList.remove('selected');
            });
            document.getElementById('tab_perm_add').checked = true;
            document.getElementById('tab_perm_edit').checked = true;
            document.getElementById('tab_perm_delete').checked = true;
            tabModal.style.display = 'block';
        }
        
        function closeTabModal() { tabModal.style.display = 'none'; }
        
        function saveTab(e) {
            e.preventDefault();
            let id = document.getElementById('tabId').value;
            let name = document.getElementById('tabName').value;
            let color = document.getElementById('tabColor').value;
            let permissions = {
                add: document.getElementById('tab_perm_add').checked,
                edit: document.getElementById('tab_perm_edit').checked,
                delete: document.getElementById('tab_perm_delete').checked
            };
            let data = (id ? 'action=updateTab&id=' + id : 'action=addTab') +
                      '&name=' + encodeURIComponent(name) + 
                      '&color=' + encodeURIComponent(color) +
                      '&permissions=' + encodeURIComponent(JSON.stringify(permissions));
            fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: data })
            .then(r => r.json())
            .then(() => location.reload());
        }
        
        function openTabSettings(event, id, name, color, permissions) {
            event.preventDefault();
            event.stopPropagation();
            currentSettingsTabId = id;
            currentSettingsTabName = name;
            currentSettingsTabColor = color;
            document.getElementById('tabSettingsModalTitle').textContent = '⚙️ Настройки вкладки "' + name + '"';
            document.getElementById('settingsTabId').value = id;
            document.getElementById('settingsTabName').value = name;
            document.querySelectorAll('#settingsColorSelector .color-option').forEach(opt => {
                opt.dataset.color === color ? opt.classList.add('selected') : opt.classList.remove('selected');
            });
            document.getElementById('settings_perm_add').checked = permissions.add || false;
            document.getElementById('settings_perm_edit').checked = permissions.edit || false;
            document.getElementById('settings_perm_delete').checked = permissions.delete || false;
            tabSettingsModal.style.display = 'block';
        }
        
        function saveTabSettings(e) {
            e.preventDefault();
            const id = document.getElementById('settingsTabId').value;
            const name = document.getElementById('settingsTabName').value;
            const color = currentSettingsTabColor;
            const permissions = {
                add: document.getElementById('settings_perm_add').checked,
                edit: document.getElementById('settings_perm_edit').checked,
                delete: document.getElementById('settings_perm_delete').checked
            };
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=updateTab&id=' + id + '&name=' + encodeURIComponent(name) + '&color=' + encodeURIComponent(color) + '&permissions=' + encodeURIComponent(JSON.stringify(permissions))
            })
            .then(r => r.json())
            .then(() => { closeTabSettingsModal(); location.reload(); });
        }
        
        function showTabUsersFromSettings() { closeTabSettingsModal(); showTabUsers(null, currentSettingsTabId, currentSettingsTabName); }
        function manageTabUsersFromSettings() { closeTabSettingsModal(); manageTabUsers(null, currentSettingsTabId, currentSettingsTabName); }
        
        function deleteTabFromSettings() {
            if (confirm('🗑️ Удалить вкладку "' + currentSettingsTabName + '"?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteTab&id=' + currentSettingsTabId
                })
                .then(r => r.json())
                .then(() => { closeTabSettingsModal(); location.reload(); });
            }
        }
        
        function closeTabSettingsModal() { tabSettingsModal.style.display = 'none'; }
        
        function manageTabs() {
            tabManageModal.style.display = 'block';
            loadTabOrder();
        }
        
        function closeTabManageModal() { tabManageModal.style.display = 'none'; }
        
        function loadTabOrder() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getTabs'
            })
            .then(r => r.json())
            .then(tabs => {
                let customTabs = tabs.filter(tab => tab.type === 'custom' && !tab.is_default);
                
                let html = '<div id="tabOrderContainer">';
                customTabs.sort((a, b) => (a.order || 0) - (b.order || 0)).forEach(tab => {
                    html += '<div class="tab-order-item" data-id="' + tab.id + '" style="' + (tab.deleted ? 'opacity:0.5;' : '') + '">';
                    html += '<span class="tab-order-handle">☰</span>';
                    html += '<span style="flex: 1; background: ' + (tab.color || '#E9ECEF') + '; padding: 3px 8px; border-radius: 12px;">' + escapeHtml(tab.name) + (tab.deleted ? ' (удалена)' : '') + '</span>';
                    html += '<span style="color: #6c757d; font-size: 11px;">порядок: ' + (tab.order || 0) + '</span>';
                    html += '</div>';
                });
                html += '</div>';
                
                document.getElementById('tabOrderList').innerHTML = html;
                
                if (sortableInstance) sortableInstance.destroy();
                let container = document.getElementById('tabOrderContainer');
                if (container) {
                    sortableInstance = new Sortable(container, {
                        handle: '.tab-order-handle',
                        animation: 150,
                        ghostClass: 'bg-light'
                    });
                }
            });
        }
        
        function saveTabOrder() {
            let order = {};
            let items = document.querySelectorAll('#tabOrderContainer .tab-order-item');
            items.forEach((item, index) => {
                let id = parseInt(item.dataset.id);
                order[id] = index * 10;
            });
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=reorderTabs&order=' + encodeURIComponent(JSON.stringify(order))
            })
            .then(r => r.json())
            .then(() => {
                alert('Порядок вкладок сохранен');
                closeTabManageModal();
                location.reload();
            });
        }
        
        function showDeletedTabs() {
            deletedTabsModal.style.display = 'block';
            loadDeletedTabs();
        }
        
        function closeDeletedTabsModal() { deletedTabsModal.style.display = 'none'; }
        
        function loadDeletedTabs() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getDeletedTabs'
            })
            .then(r => r.json())
            .then(tabs => {
                let html = '';
                if (tabs.length > 0) {
                    tabs.forEach(tab => {
                        html += '<div class="user-item">';
                        html += '<div class="user-info">';
                        html += '<span class="user-name">' + escapeHtml(tab.name) + '</span>';
                        html += '<span class="user-role">Удалена: ' + (tab.deleted_at || '') + ' пользователем ' + escapeHtml(tab.deleted_by_fullname || tab.deleted_by || 'неизвестно') + '</span>';
                        html += '</div>';
                        html += '<button class="btn restore btn-sm" onclick="restoreTab(' + tab.id + ')">♻️ Восстановить</button>';
                        html += '</div>';
                    });
                } else {
                    html = '<p style="text-align: center; color: #6c757d; padding: 15px;">Нет удаленных вкладок</p>';
                }
                document.getElementById('deletedTabsList').innerHTML = html;
            });
        }
        
        function restoreTab(tab_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=restoreTab&id=' + tab_id
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    loadDeletedTabs();
                    setTimeout(() => location.reload(), 1000);
                }
            });
        }
        
        // Функции для просмотра пользователей вкладки
        function showTabUsers(event, tab_id, tab_name) {
            if (event) { event.preventDefault(); event.stopPropagation(); }
            document.getElementById('tabUsersModalTitle').textContent = '👥 Пользователи с доступом к вкладке "' + tab_name + '"';
            tabUsersModal.style.display = 'block';
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getUsersWithTabAccess&tab_id=' + tab_id
            })
            .then(r => r.json())
            .then(users => {
                let html = '';
                if (users.length > 0) {
                    users.forEach(user => {
                        html += '<div class="user-item">';
                        html += '<div class="user-info">';
                        html += '<span class="user-name">' + escapeHtml(user.fullname) + ' (' + escapeHtml(user.username) + ')</span>';
                        html += '<span class="user-role">' + (user.role == 'admin' ? 'Администратор' : 'Пользователь') + '</span>';
                        html += '</div>';
                        html += '<span class="access-badge ' + user.access + '">' + (user.access == 'full' ? 'Полный доступ' : 'Разрешен') + '</span>';
                        html += '</div>';
                    });
                } else {
                    html = '<p style="text-align: center; color: #6c757d; padding: 15px;">Нет пользователей с доступом к этой вкладке</p>';
                }
                document.getElementById('tabUsersList').innerHTML = html;
            });
        }
        
        function closeTabUsersModal() { tabUsersModal.style.display = 'none'; }
        
        // Функции для управления доступом к вкладке
        function manageTabUsers(event, tab_id, tab_name) {
            if (event) { event.preventDefault(); event.stopPropagation(); }
            document.getElementById('tabManageUsersModalTitle').textContent = '🔑 Управление доступом к вкладке "' + tab_name + '"';
            tabManageUsersModal.style.display = 'block';
            loadTabAccessUsers(tab_id);
        }
        
        function loadTabAccessUsers(tab_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getUsersWithTabAccess&tab_id=' + tab_id
            })
            .then(r => r.json())
            .then(users => {
                let grantedHtml = '';
                if (users.length > 0) {
                    users.forEach(user => {
                        grantedHtml += '<div class="user-item">';
                        grantedHtml += '<div class="user-info">';
                        grantedHtml += '<span class="user-name">' + escapeHtml(user.fullname) + ' (' + escapeHtml(user.username) + ')</span>';
                        grantedHtml += '<span class="user-role">' + (user.role == 'admin' ? 'Администратор' : 'Пользователь') + '</span>';
                        grantedHtml += '</div>';
                        grantedHtml += '<span class="access-badge ' + user.access + '">' + (user.access == 'full' ? 'Полный доступ' : 'Разрешен') + '</span>';
                        if (user.role != 'admin') {
                            grantedHtml += '<button class="btn del btn-sm" onclick="revokeTabAccess(' + tab_id + ', ' + user.id + ')">Отозвать</button>';
                        }
                        grantedHtml += '</div>';
                    });
                } else {
                    grantedHtml = '<p style="text-align: center; color: #6c757d; padding: 15px;">Нет пользователей с доступом</p>';
                }
                document.getElementById('tabGrantedUsersList').innerHTML = grantedHtml;
            });
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getUsersWithoutTabAccess&tab_id=' + tab_id
            })
            .then(r => r.json())
            .then(users => {
                let availableHtml = '';
                if (users.length > 0) {
                    users.forEach(user => {
                        availableHtml += '<div class="user-item">';
                        availableHtml += '<div class="user-info">';
                        availableHtml += '<span class="user-name">' + escapeHtml(user.fullname) + ' (' + escapeHtml(user.username) + ')</span>';
                        availableHtml += '<span class="user-role">Пользователь</span>';
                        availableHtml += '</div>';
                        availableHtml += '<button class="btn btn-sm" onclick="grantTabAccess(' + tab_id + ', ' + user.id + ')">Предоставить</button>';
                        availableHtml += '</div>';
                    });
                } else {
                    availableHtml = '<p style="text-align: center; color: #6c757d; padding: 15px;">Все пользователи уже имеют доступ</p>';
                }
                document.getElementById('tabAvailableUsersList').innerHTML = availableHtml;
            });
        }
        
        function grantTabAccess(tab_id, user_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=grantTabAccess&tab_id=' + tab_id + '&user_id=' + user_id
            })
            .then(r => r.json())
            .then(data => { if (data.ok) loadTabAccessUsers(tab_id); });
        }
        
        function revokeTabAccess(tab_id, user_id) {
            if (confirm('Отозвать доступ у пользователя?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=revokeTabAccess&tab_id=' + tab_id + '&user_id=' + user_id
                })
                .then(r => r.json())
                .then(data => { if (data.ok) loadTabAccessUsers(tab_id); });
            }
        }
        
        function closeTabManageUsersModal() { tabManageUsersModal.style.display = 'none'; }
        
        // Функции для записей
        function openModal() {
            modal.style.display = 'block';
            document.getElementById('modalTitle').textContent = '➕ Добавить запись';
            document.getElementById('itemId').value = '';
            
            // Заголовок
            document.getElementById('titleSelect').value = '<?=$last_title?>';
            document.getElementById('title').style.display = 'none';
            document.getElementById('title').value = '<?=$last_title?>';
            
            // Система
            const systemSelect = document.getElementById('systemSelect');
            const systemInput = document.getElementById('system');
            let sysFound = false;
            for (let i = 0; i < systemSelect.options.length; i++) {
                if (systemSelect.options[i].value === '<?=$last_system?>') {
                    systemSelect.value = '<?=$last_system?>';
                    systemInput.style.display = 'none';
                    systemInput.value = '<?=$last_system?>';
                    sysFound = true;
                    break;
                }
            }
            if (!sysFound && '<?=$last_system?>' !== '') {
                systemSelect.value = '--custom--';
                systemInput.style.display = 'block';
                systemInput.value = '<?=$last_system?>';
            } else if ('<?=$last_system?>' === '') {
                systemSelect.value = '';
                systemInput.style.display = 'none';
                systemInput.value = '';
            }
            
            // Объект
            const objectSelect = document.getElementById('objectSelect');
            const objectInput = document.getElementById('object');
            let objFound = false;
            for (let i = 0; i < objectSelect.options.length; i++) {
                if (objectSelect.options[i].value === '<?=$last_object?>') {
                    objectSelect.value = '<?=$last_object?>';
                    objectInput.style.display = 'none';
                    objectInput.value = '<?=$last_object?>';
                    objFound = true;
                    break;
                }
            }
            if (!objFound && '<?=$last_object?>' !== '') {
                objectSelect.value = '--custom--';
                objectInput.style.display = 'block';
                objectInput.value = '<?=$last_object?>';
            } else if ('<?=$last_object?>' === '') {
                objectSelect.value = '';
                objectInput.style.display = 'none';
                objectInput.value = '';
            }
            
            document.getElementById('description').value = '';
            
            // Цвет
            document.getElementById('selectedColor').value = '<?=$last_color?>';
            currentColor = '<?=$last_color?>';
            document.querySelectorAll('#colorSelector .color-option').forEach(opt => {
                opt.dataset.color === '<?=$last_color?>' ? opt.classList.add('selected') : opt.classList.remove('selected');
            });
            
            onTitleChange();
            onSystemChange();
            onObjectChange();
            loadSystems();
            loadObjects();
        }
        
        function closeModal() { modal.style.display = 'none'; }
        
        function editItem(id, tab_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get&id=' + id + '&tab_id=' + tab_id
            })
            .then(r => r.json())
            .then(d => {
                document.getElementById('modalTitle').textContent = '✏️ Редактировать запись';
                document.getElementById('itemId').value = id;
                document.getElementById('tabId').value = tab_id;
                
                // Заголовок
                const titleSelect = document.getElementById('titleSelect');
                const titleInput = document.getElementById('title');
                let found = false;
                for (let i = 0; i < titleSelect.options.length; i++) {
                    if (titleSelect.options[i].value === d.title) {
                        titleSelect.value = d.title;
                        titleInput.style.display = 'none';
                        titleInput.value = d.title;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    titleSelect.value = '--custom--';
                    titleInput.style.display = 'block';
                    titleInput.value = d.title;
                }
                onTitleChange();
                
                // Система
                const systemSelect = document.getElementById('systemSelect');
                const systemInput = document.getElementById('system');
                let sysFound = false;
                for (let i = 0; i < systemSelect.options.length; i++) {
                    if (systemSelect.options[i].value === d.system) {
                        systemSelect.value = d.system;
                        systemInput.style.display = 'none';
                        systemInput.value = d.system;
                        sysFound = true;
                        break;
                    }
                }
                if (!sysFound && d.system) {
                    systemSelect.value = '--custom--';
                    systemInput.style.display = 'block';
                    systemInput.value = d.system;
                } else if (!d.system) {
                    systemSelect.value = '';
                    systemInput.style.display = 'none';
                    systemInput.value = '';
                }
                onSystemChange();
                
                // Объект
                const objectSelect = document.getElementById('objectSelect');
                const objectInput = document.getElementById('object');
                let objFound = false;
                for (let i = 0; i < objectSelect.options.length; i++) {
                    if (objectSelect.options[i].value === d.object) {
                        objectSelect.value = d.object;
                        objectInput.style.display = 'none';
                        objectInput.value = d.object;
                        objFound = true;
                        break;
                    }
                }
                if (!objFound && d.object) {
                    objectSelect.value = '--custom--';
                    objectInput.style.display = 'block';
                    objectInput.value = d.object;
                } else if (!d.object) {
                    objectSelect.value = '';
                    objectInput.style.display = 'none';
                    objectInput.value = '';
                }
                onObjectChange();
                
                document.getElementById('description').value = d.description || '';
                
                let color = d.color || '#FFFFFF';
                document.getElementById('selectedColor').value = color;
                currentColor = color;
                document.querySelectorAll('#colorSelector .color-option').forEach(opt => {
                    opt.dataset.color === color ? opt.classList.add('selected') : opt.classList.remove('selected');
                });
                
                modal.style.display = 'block';
                loadSystems();
                loadObjects();
            });
        }
        
        function saveItem(e) {
            e.preventDefault();
            let id = document.getElementById('itemId').value;
            let titleSelect = document.getElementById('titleSelect');
            let titleInput = document.getElementById('title');
            let title = titleSelect.value === '--custom--' ? titleInput.value : titleSelect.value;
            
            let systemSelect = document.getElementById('systemSelect');
            let systemInput = document.getElementById('system');
            let system = systemSelect.value === '--custom--' ? systemInput.value : systemSelect.value;
            
            let objectSelect = document.getElementById('objectSelect');
            let objectInput = document.getElementById('object');
            let object = objectSelect.value === '--custom--' ? objectInput.value : objectSelect.value;
            
            let desc = document.getElementById('description').value;
            let color = document.getElementById('selectedColor').value;
            let tab_id = document.getElementById('tabId').value;
            
            if (!title) { alert('Пожалуйста, введите заголовок'); return; }
            
            let act = id ? 'update' : 'add';
            let data = 'action=' + act + '&title=' + encodeURIComponent(title) + 
                      '&system=' + encodeURIComponent(system) +
                      '&object=' + encodeURIComponent(object) +
                      '&description=' + encodeURIComponent(desc) + 
                      '&color=' + encodeURIComponent(color) +
                      '&tab_id=' + tab_id;
            if (id) data += '&id=' + id;
            
            fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: data })
            .then(r => r.json())
            .then(() => location.reload());
        }
        
        function deleteItem(id, tab_id) {
            if (confirm('🗑️ Удалить запись?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=delete&id=' + id + '&tab_id=' + tab_id
                })
                .then(() => location.reload());
            }
        }
        
        function restoreItem(id, tab_id) {
            if (confirm('Восстановить запись? Выберите способ:\nОК - как есть\nОтмена - как новая')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=restore&id=' + id + '&tab_id=' + tab_id + '&as_new=false'
                })
                .then(() => location.reload());
            } else {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=restore&id=' + id + '&tab_id=' + tab_id + '&as_new=true'
                })
                .then(() => location.reload());
            }
        }
        
        function toggleCompleted(id, tab_id, checkbox) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=toggleCompleted&id=' + id + '&tab_id=' + tab_id
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    checkbox.checked = !checkbox.checked;
                } else {
                    checkbox.checked = data.ok;
                    const row = document.getElementById(`row-${id}`);
                    data.ok ? row.classList.add('completed-row') : row.classList.remove('completed-row');
                }
            });
        }
        
        function toggleActual(id, tab_id, checkbox) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=toggleActual&id=' + id + '&tab_id=' + tab_id
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    checkbox.checked = !checkbox.checked;
                } else {
                    checkbox.checked = data.ok;
                    const row = document.getElementById(`row-${id}`);
                    !checkbox.checked ? row.classList.add('not-actual-row') : row.classList.remove('not-actual-row');
                }
            });
        }
        
        // Функции для пользователей
        function showUsers() { usersModal.style.display = 'block'; loadUsers(); }
        function closeUsersModal() { usersModal.style.display = 'none'; }
        
        function loadUsers() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getUsers'
            })
            .then(r => r.json())
            .then(users => {
                let html = '<table class="table" style="margin-top: 10px;"><thead><tr><th>ID</th><th>Логин</th><th>Полное имя</th><th>Роль</th><th>Client ID</th><th>Права</th><th>Доступные вкладки</th><th>Действия</th></tr></thead><tbody>';
                users.forEach(user => {
                    html += '<tr>';
                    html += '<td>' + user.id + '</td>';
                    html += '<td><strong>' + escapeHtml(user.username) + '</strong></td>';
                    html += '<td>' + escapeHtml(user.fullname || user.username) + '</td>';
                    html += '<td><span class="role-badge">' + user.role + '</span></td>';
                    html += '<td><span class="client-id-badge">' + (user.client_id || '') + '</span></td>';
                    html += '<td style="font-size: 12px;">';
                    if (user.role == 'admin') {
                        html += 'Полный доступ';
                    } else {
                        let perms = user.permissions || {};
                        let items = [];
                        if (perms.view) items.push('👁️ просмотр');
                        if (perms.add) items.push('➕ добавление');
                        if (perms.edit) items.push('✏️ ред' + (perms.edit_own ? ' (только свои)' : ''));
                        if (perms.delete) items.push('🗑️ удал' + (perms.delete_own ? ' (только свои)' : ''));
                        if (perms.comment) items.push('💬 коммент' + (perms.comment_own ? ' (только свои)' : ''));
                        if (perms.change_status) items.push('⚡ статус' + (perms.change_status_own ? ' (только свои)' : ''));
                        if (perms.create_tab) items.push('➕ созд.вкладок' + (perms.create_tab_own ? ' (только свои)' : ''));
                        if (perms.view_tab_users) items.push('👥 просмотр пользователей' + (perms.view_tab_users_own ? ' (только свои)' : ''));
                        if (perms.manage_tab_users) items.push('🔑 упр. доступом' + (perms.manage_tab_users_own ? ' (только свои)' : ''));
                        if (perms.view_files) items.push('📄 просмотр файлов' + (perms.view_files_own ? ' (только свои)' : ''));
                        if (perms.upload_files) items.push('📎 загрузка файлов' + (perms.upload_files_own ? ' (только к своим)' : ''));
                        if (perms.delete_files) items.push('🗑️ удал.файлов' + (perms.delete_files_own ? ' (только свои)' : ''));
                        if (perms.delete_comment) items.push('🗑️ уд.комм' + (perms.delete_comment_own ? ' (только свои)' : ''));
                        html += items.join('<br>');
                    }
                    html += '</td>';
                    html += '<td style="font-size: 12px;">';
                    if (user.role == 'admin') {
                        html += 'Все вкладки';
                    } else {
                        let tabPerms = user.tab_permissions || [];
                        let tabNames = [];
                        <?php foreach ($all_tabs as $tab): ?>
                        if (tabPerms.includes(<?=$tab['id']?>)) {
                            tabNames.push('<?=htmlspecialchars($tab['name'])?>');
                        }
                        <?php endforeach; ?>
                        html += tabNames.join('<br>') || 'Нет доступа';
                    }
                    html += '</td>';
                    html += '<td class="actions">';
                    html += '<button class="btn edit btn-sm" onclick="editUser(' + user.id + ')">✏️</button>';
                    if (user.username !== 'admin') {
                        html += '<button class="btn del btn-sm" onclick="deleteUser(' + user.id + ')">🗑️</button>';
                    }
                    html += '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                document.getElementById('usersList').innerHTML = html;
            });
        }
        
        function openUserModal() {
            userModal.style.display = 'block';
            document.getElementById('userModalTitle').textContent = '➕ Добавить пользователя';
            document.getElementById('userId').value = '';
            document.getElementById('fullname').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('perm_view').checked = true;
            document.getElementById('perm_add').checked = false;
            document.getElementById('perm_edit').checked = false;
            document.getElementById('perm_edit_own').checked = false;
            document.getElementById('perm_delete').checked = false;
            document.getElementById('perm_delete_own').checked = false;
            document.getElementById('perm_comment').checked = true;
            document.getElementById('perm_comment_own').checked = false;
            document.getElementById('perm_change_status').checked = false;
            document.getElementById('perm_change_status_own').checked = false;
            document.getElementById('perm_create_tab').checked = false;
            document.getElementById('perm_create_tab_own').checked = false;
            document.getElementById('perm_view_tab_users').checked = false;
            document.getElementById('perm_view_tab_users_own').checked = false;
            document.getElementById('perm_manage_tab_users').checked = false;
            document.getElementById('perm_manage_tab_users_own').checked = false;
            document.getElementById('perm_view_files').checked = false;
            document.getElementById('perm_view_files_own').checked = false;
            document.getElementById('perm_upload_files').checked = false;
            document.getElementById('perm_upload_files_own').checked = false;
            document.getElementById('perm_delete_files').checked = false;
            document.getElementById('perm_delete_files_own').checked = false;
            document.getElementById('perm_delete_comment').checked = false;
            document.getElementById('perm_delete_comment_own').checked = false;
            document.querySelectorAll('.tab-permission-checkbox').forEach(cb => cb.checked = false);
        }
        
        function selectAllPermissions(select) {
            document.querySelectorAll('#userModal input[type="checkbox"]').forEach(cb => {
                if (cb.id !== 'perm_view' || !select) cb.checked = select;
            });
        }
        
        function closeUserModal() { userModal.style.display = 'none'; }
        
        function editUser(id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getUsers'
            })
            .then(r => r.json())
            .then(users => {
                let user = users.find(u => u.id == id);
                if (user) {
                    document.getElementById('userModalTitle').textContent = '✏️ Редактировать пользователя';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('fullname').value = user.fullname || user.username;
                    document.getElementById('password').value = '';
                    let perms = user.permissions || {};
                    document.getElementById('perm_view').checked = perms.view ?? true;
                    document.getElementById('perm_add').checked = perms.add ?? false;
                    document.getElementById('perm_edit').checked = perms.edit ?? false;
                    document.getElementById('perm_edit_own').checked = perms.edit_own ?? false;
                    document.getElementById('perm_delete').checked = perms.delete ?? false;
                    document.getElementById('perm_delete_own').checked = perms.delete_own ?? false;
                    document.getElementById('perm_comment').checked = perms.comment ?? false;
                    document.getElementById('perm_comment_own').checked = perms.comment_own ?? false;
                    document.getElementById('perm_change_status').checked = perms.change_status ?? false;
                    document.getElementById('perm_change_status_own').checked = perms.change_status_own ?? false;
                    document.getElementById('perm_create_tab').checked = perms.create_tab ?? false;
                    document.getElementById('perm_create_tab_own').checked = perms.create_tab_own ?? false;
                    document.getElementById('perm_view_tab_users').checked = perms.view_tab_users ?? false;
                    document.getElementById('perm_view_tab_users_own').checked = perms.view_tab_users_own ?? false;
                    document.getElementById('perm_manage_tab_users').checked = perms.manage_tab_users ?? false;
                    document.getElementById('perm_manage_tab_users_own').checked = perms.manage_tab_users_own ?? false;
                    document.getElementById('perm_view_files').checked = perms.view_files ?? false;
                    document.getElementById('perm_view_files_own').checked = perms.view_files_own ?? false;
                    document.getElementById('perm_upload_files').checked = perms.upload_files ?? false;
                    document.getElementById('perm_upload_files_own').checked = perms.upload_files_own ?? false;
                    document.getElementById('perm_delete_files').checked = perms.delete_files ?? false;
                    document.getElementById('perm_delete_files_own').checked = perms.delete_files_own ?? false;
                    document.getElementById('perm_delete_comment').checked = perms.delete_comment ?? false;
                    document.getElementById('perm_delete_comment_own').checked = perms.delete_comment_own ?? false;
                    let tabPerms = user.tab_permissions || [];
                    document.querySelectorAll('.tab-permission-checkbox').forEach(cb => {
                        cb.checked = tabPerms.includes(parseInt(cb.value));
                    });
                    userModal.style.display = 'block';
                }
            });
        }
        
        function saveUser(e) {
            e.preventDefault();
            let id = document.getElementById('userId').value;
            let username = document.getElementById('username').value;
            let fullname = document.getElementById('fullname').value;
            let password = document.getElementById('password').value;
            let permissions = {
                view: document.getElementById('perm_view').checked,
                add: document.getElementById('perm_add').checked,
                edit: document.getElementById('perm_edit').checked,
                edit_own: document.getElementById('perm_edit_own').checked,
                delete: document.getElementById('perm_delete').checked,
                delete_own: document.getElementById('perm_delete_own').checked,
                comment: document.getElementById('perm_comment').checked,
                comment_own: document.getElementById('perm_comment_own').checked,
                change_status: document.getElementById('perm_change_status').checked,
                change_status_own: document.getElementById('perm_change_status_own').checked,
                create_tab: document.getElementById('perm_create_tab').checked,
                create_tab_own: document.getElementById('perm_create_tab_own').checked,
                view_tab_users: document.getElementById('perm_view_tab_users').checked,
                view_tab_users_own: document.getElementById('perm_view_tab_users_own').checked,
                manage_tab_users: document.getElementById('perm_manage_tab_users').checked,
                manage_tab_users_own: document.getElementById('perm_manage_tab_users_own').checked,
                view_files: document.getElementById('perm_view_files').checked,
                view_files_own: document.getElementById('perm_view_files_own').checked,
                upload_files: document.getElementById('perm_upload_files').checked,
                upload_files_own: document.getElementById('perm_upload_files_own').checked,
                delete_files: document.getElementById('perm_delete_files').checked,
                delete_files_own: document.getElementById('perm_delete_files_own').checked,
                delete_comment: document.getElementById('perm_delete_comment').checked,
                delete_comment_own: document.getElementById('perm_delete_comment_own').checked
            };
            let tabPermissions = [];
            document.querySelectorAll('.tab-permission-checkbox:checked').forEach(cb => {
                tabPermissions.push(parseInt(cb.value));
            });
            let data = 'action=' + (id ? 'updateUser' : 'addUser') +
                      '&username=' + encodeURIComponent(username) +
                      '&fullname=' + encodeURIComponent(fullname) +
                      '&password=' + encodeURIComponent(password) +
                      '&permissions=' + encodeURIComponent(JSON.stringify(permissions)) +
                      '&tab_permissions=' + encodeURIComponent(JSON.stringify(tabPermissions));
            if (id) data += '&id=' + id;
            fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: data })
            .then(r => r.json())
            .then(() => { closeUserModal(); loadUsers(); });
        }
        
        function deleteUser(id) {
            if (confirm('🗑️ Удалить пользователя?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteUser&id=' + id
                })
                .then(r => r.json())
                .then(() => loadUsers());
            }
        }
        
        // Функции для комментариев
        function openReplyModal(item_id, item_title, tab_id) {
            replyModal.style.display = 'block';
            document.getElementById('replyItemId').value = item_id;
            document.getElementById('replyTabId').value = tab_id;
            document.getElementById('replyOriginalTitle').textContent = 'Запись: ' + item_title;
            document.getElementById('replyContent').value = '';
            loadReplies(item_id);
        }
        
        function closeReplyModal() { replyModal.style.display = 'none'; }
        
        function loadReplies(item_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=getReplies&item_id=' + item_id
            })
            .then(r => r.json())
            .then(replies => {
                let container = document.getElementById('repliesContainer');
                if (replies.length > 0) {
                    let html = '';
                    replies.forEach(reply => {
                        html += '<div class="reply-item">';
                        html += '<div class="reply-header">';
                        html += '<span>' + escapeHtml(reply.author_fullname || reply.author) + '</span>';
                        html += '<span>' + reply.created_at + ' (ID: ' + (reply.author_client_id || '') + ')</span>';
                        html += '</div>';
                        html += '<div class="reply-content">' + escapeHtml(reply.content) + '</div>';
                        let canDelete = ('<?=$current_user['role']?>' === 'admin') || 
                                        (reply.author_id == '<?=$current_user['id']?>' && <?=($db->checkPermission($current_user, 'delete_comment_own', null) ? 'true' : 'false')?>) || 
                                        <?=($db->checkPermission($current_user, 'delete_comment', null) ? 'true' : 'false')?>;
                        if (canDelete) {
                            html += '<div style="text-align: right; margin-top: 6px;"><button class="btn del btn-sm" onclick="deleteReply(' + reply.id + ')">🗑️</button></div>';
                        }
                        html += '</div>';
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="text-align: center; color: #6c757d; padding: 15px;">Пока нет комментариев</p>';
                }
            });
        }
        
        function saveReply(e) {
            e.preventDefault();
            let item_id = document.getElementById('replyItemId').value;
            let tab_id = document.getElementById('replyTabId').value;
            let content = document.getElementById('replyContent').value;
            if (!content.trim()) { alert('Введите текст комментария'); return; }
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=addReply&item_id=' + item_id + '&tab_id=' + tab_id + '&content=' + encodeURIComponent(content)
            })
            .then(r => r.json())
            .then(() => {
                loadReplies(item_id);
                document.getElementById('replyContent').value = '';
                setTimeout(() => location.reload(), 500);
            });
        }
        
        function deleteReply(id) {
            if (confirm('🗑️ Удалить комментарий?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteReply&id=' + id
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        let item_id = document.getElementById('replyItemId').value;
                        loadReplies(item_id);
                        setTimeout(() => location.reload(), 500);
                    } else {
                        alert('У вас нет прав на удаление этого комментария');
                    }
                });
            }
        }
        
        // Функции для файлов - ИСПРАВЛЕНО: передаем tab_id
        function openFilesModal(item_id, item_title, tab_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get&id=' + item_id + '&tab_id=' + tab_id
            })
            .then(r => r.json())
            .then(item => {
                <?php if (!$can_view_files): ?>
                <?php
                $can_view_this_item = $db->checkPermission($current_user, 'view_files', $item['author_id'] ?? null);
                if (!$can_view_this_item): ?>
                alert('У вас нет прав на просмотр файлов в этой записи');
                return;
                <?php endif; ?>
                <?php endif; ?>
                currentFilesItemId = item_id;
                currentFilesTabId = tab_id; // ВАЖНО: сохраняем tab_id
                currentFilesItemTitle = item_title;
                currentFilesTab = 'active';
                filesModal.style.display = 'block';
                document.getElementById('filesModalTitle').textContent = '📎 Файлы записи "' + item_title + '"';
                document.getElementById('filesOriginalTitle').textContent = 'Запись: ' + item_title;
                loadFiles(); // loadFiles() будет использовать currentFilesTabId
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=markFileAsRead&item_id=' + item_id + '&tab_id=' + tab_id
                });
            });
        }
        
        function closeFilesModal() {
            filesModal.style.display = 'none';
            currentFilesItemId = null;
            currentFilesTabId = null;
            currentFilesItemTitle = '';
        }
        
        function showFilesTab(tab) { currentFilesTab = tab; loadFiles(); }
        
        function loadFiles() {
            const action = currentFilesTab === 'active' ? 'getItemFiles' : 'getDeletedFiles';
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=' + action + '&item_id=' + currentFilesItemId + '&tab_id=' + currentFilesTabId // ВАЖНО: передаем tab_id
            })
            .then(r => r.json())
            .then(files => {
                let html = '';
                if (files.length > 0) {
                    files.forEach(file => {
                        html += '<div class="file-item">';
                        html += '<div class="file-header">';
                        html += '<span class="file-name">' + escapeHtml(file.original_name) + '</span>';
                        html += '<span class="file-meta">' + (file.created_at || '') + ' от ' + escapeHtml(file.created_by_fullname || file.created_by) + '</span>';
                        html += '</div>';
                        
                        if (file.is_image) {
                            html += '<div class="file-preview">';
                            html += '<img src="?preview_file=' + file.id + '" onclick="this.classList.toggle(\'enlarged\')" title="Нажмите для увеличения" onerror="this.style.display=\'none\'">';
                            html += '</div>';
                        }
                        
                        html += '<div class="file-meta">Размер: ' + formatFileSize(file.filesize) + ' | Тип: ' + (file.mime_type || 'неизвестно') + '</div>';
                        html += '<div class="file-actions">';
                        
                        html += '<a href="?download_file=' + file.id + '" class="btn btn-sm" download>📥 Скачать</a>';
                        
                        if (currentFilesTab === 'active') {
                            <?php if ($can_delete_files): ?>
                            html += '<button class="btn del btn-sm" onclick="deleteFile(' + file.id + ')">🗑️ Удалить</button>';
                            <?php endif; ?>
                        } else {
                            html += '<button class="btn restore btn-sm" onclick="restoreFile(' + file.id + ')">♻️ Восстановить</button>';
                        }
                        html += '</div>';
                        html += '</div>';
                    });
                } else {
                    html = '<p style="text-align: center; color: #6c757d; padding: 15px;">Файлы не найдены</p>';
                }
                document.getElementById('filesList').innerHTML = html;
            });
        }
        
        function handleDragLeave(event) {
            event.preventDefault();
            event.stopPropagation();
            document.querySelector('.file-upload-area').classList.remove('dragover');
        }
        function handleDragOver(event) {
            event.preventDefault();
            event.stopPropagation();
            document.querySelector('.file-upload-area').classList.add('dragover');
        }
        
        function handleDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            document.querySelector('.file-upload-area').classList.remove('dragover');
            const files = event.dataTransfer.files;
            uploadFiles(files);
        }
        
        function handleFileSelect() {
            const files = document.getElementById('fileInput').files;
            uploadFiles(files);
        }
        
        function uploadFiles(files) {
            if (!currentFilesItemId || !currentFilesTabId) return;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const formData = new FormData();
                formData.append('action', 'upload_file');
                formData.append('item_id', currentFilesItemId);
                formData.append('tab_id', currentFilesTabId);
                formData.append('file', file);
                fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        loadFiles();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('Ошибка при загрузке файла: ' + (data.error || 'неизвестная ошибка'));
                    }
                });
            }
        }
        
        function deleteFile(file_id) {
            if (confirm('🗑️ Удалить файл?')) {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=deleteFile&file_id=' + file_id
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        loadFiles();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('Ошибка при удалении файла');
                    }
                });
            }
        }
        
        function restoreFile(file_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=restoreFile&file_id=' + file_id
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    loadFiles();
                    setTimeout(() => location.reload(), 1000);
                }
            });
        }
        
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            return (bytes / (1024 * 1024 * 1024)).toFixed(1) + ' GB';
        }
        
        function openFilesUploadModal(item_id, tab_id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get&id=' + item_id + '&tab_id=' + tab_id
            })
            .then(r => r.json())
            .then(item => { openFilesModal(item_id, item.title, tab_id); });
        }
        
        // Функции для экспорта
        function exportExcel() {
            let data = [];
            let isAllTab = <?=$current_tab_data && $current_tab_data['type'] == 'all' ? 'true' : 'false'?>;
            if (isAllTab) {
                data.push(['№', 'Вкладка', 'Заголовок', 'Система', 'Объект', 'Описание', 'Файлы', 'Автор', 'ID автора', 'Комментарии', 'Выполнено', 'Актуально', 'Дата создания', 'Дата изменения', 'Кем изменено']);
            } else {
                data.push(['№', 'Заголовок', 'Система', 'Объект', 'Описание', 'Файлы', 'Автор', 'ID автора', 'Комментарии', 'Выполнено', 'Актуально', 'Дата создания', 'Дата изменения', 'Кем изменено']);
            }
            let rows = document.querySelectorAll('.table tbody tr');
            rows.forEach(row => {
                if (!row.querySelector('.empty-state')) {
                    let cols = row.querySelectorAll('td');
                    if (cols.length > 1) {
                        let num = cols[0].innerText.trim().replace(/[●○]/g, '').trim();
                        let colIndex = 1;
                        if (isAllTab) {
                            let tabName = cols[colIndex]?.innerText.trim() || ''; colIndex++;
                            let titleSystem = cols[colIndex]?.innerText.trim() || ''; colIndex++;
                            let systemVal = cols[colIndex]?.innerText.trim() || ''; colIndex++;
                            let objectVal = cols[colIndex]?.innerText.trim() || ''; colIndex++;
                            let desc = cols[colIndex]?.innerText.split('Комментарии')[0].trim() || ''; colIndex++;
                            let files = cols[colIndex]?.innerText.trim() || '0'; colIndex++;
                            let author = cols[colIndex]?.querySelector('.author-name')?.innerText.trim() || ''; colIndex++;
                            let authorId = cols[colIndex]?.innerText.replace('🖥️', '').trim() || ''; colIndex++;
                            let replyInfo = cols[colIndex-3]?.querySelector('.last-reply')?.innerText || '';
                            let replyCount = replyInfo.match(/Комментарии \((\d+)\)/)?.[1] || '0'; colIndex++;
                            let statusCells = cols[colIndex]?.querySelectorAll('.status-item') || [];
                            let completed = statusCells[0]?.querySelector('input')?.checked ? 'Да' : 'Нет';
                            let actual = statusCells[1]?.querySelector('input')?.checked ? 'Да' : 'Нет';
                            let dates = cols[colIndex]?.querySelector('div:first-child')?.innerHTML || '';
                            let created = dates.split('<span')[0]?.replace('📅', '').trim() || '';
                            let updatedInfo = dates.match(/✏️ (.*?)<\/span>/);
                            let updated = updatedInfo ? updatedInfo[1].split('(')[0].trim() : '';
                            let updatedBy = updatedInfo && updatedInfo[1].includes('(') ? updatedInfo[1].match(/\((.*?)\)/)?.[1] || '' : '';
                            data.push([num, tabName, titleSystem, systemVal, objectVal, desc, files, author, authorId, replyCount, completed, actual, created, updated, updatedBy]);
                        } else {
                            let titleSystem = cols[1]?.innerText.trim() || '';
                            let systemVal = cols[2]?.innerText.trim() || '';
                            let objectVal = cols[3]?.innerText.trim() || '';
                            let desc = cols[4]?.innerText.split('Комментарии')[0].trim() || '';
                            let files = cols[5]?.innerText.trim() || '0';
                            let author = cols[6]?.querySelector('.author-name')?.innerText.trim() || '';
                            let authorId = cols[6]?.querySelector('.author-id')?.innerText.replace('🖥️', '').trim() || '';
                            let replyInfo = cols[4]?.querySelector('.last-reply')?.innerText || '';
                            let replyCount = replyInfo.match(/Комментарии \((\d+)\)/)?.[1] || '0';
                            let statusCells = cols[7]?.querySelectorAll('.status-item') || [];
                            let completed = statusCells[0]?.querySelector('input')?.checked ? 'Да' : 'Нет';
                            let actual = statusCells[1]?.querySelector('input')?.checked ? 'Да' : 'Нет';
                            let dates = cols[7]?.querySelector('div:first-child')?.innerHTML || '';
                            let created = dates.split('<span')[0]?.replace('📅', '').trim() || '';
                            let updatedInfo = dates.match(/✏️ (.*?)<\/span>/);
                            let updated = updatedInfo ? updatedInfo[1].split('(')[0].trim() : '';
                            let updatedBy = updatedInfo && updatedInfo[1].includes('(') ? updatedInfo[1].match(/\((.*?)\)/)?.[1] || '' : '';
                            data.push([num, titleSystem, systemVal, objectVal, desc, files, author, authorId, replyCount, completed, actual, created, updated, updatedBy]);
                        }
                    }
                }
            });
            let wb = XLSX.utils.book_new();
            let ws = XLSX.utils.aoa_to_sheet(data);
            XLSX.utils.book_append_sheet(wb, ws, 'Контейнеры');
            XLSX.writeFile(wb, 'containers_export.xlsx');
        }
        
        function exportHTML(tab_id) {
            // Получаем текущие значения всех фильтров
            const params = new URLSearchParams(window.location.search);
            
            // Устанавливаем или обновляем значения фильтров из элементов формы
            params.set('author', document.getElementById('filterAuthor').value);
            params.set('title', document.getElementById('filterTitle').value);
            params.set('system', document.getElementById('filterSystem').value);
            params.set('object', document.getElementById('filterObject').value);
            params.set('search', document.getElementById('filterSearch').value);
            params.set('status', document.getElementById('filterStatus').value);
            params.set('read_status', document.getElementById('filterReadStatus').value);
            params.set('files', document.getElementById('filterFiles').value);
            params.set('tab', tab_id);
            
            // Открываем окно с полным набором параметров
            window.open('?export_html=1&' + params.toString(), '_blank');
        }
        
        function escapeHtml(text) {
            let div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        document.querySelectorAll('.modal-content').forEach(content => {
            content.addEventListener('click', (e) => e.stopPropagation());
        });
        
        window.onclick = (e) => { 
            if (e.target == modal) modal.style.display = 'none';
            if (e.target == tabModal) tabModal.style.display = 'none';
            if (e.target == tabManageModal) tabManageModal.style.display = 'none';
            if (e.target == usersModal) usersModal.style.display = 'none';
            if (e.target == userModal) userModal.style.display = 'none';
            if (e.target == replyModal) replyModal.style.display = 'none';
            if (e.target == filesModal) filesModal.style.display = 'none';
            if (e.target == tabUsersModal) tabUsersModal.style.display = 'none';
            if (e.target == tabManageUsersModal) tabManageUsersModal.style.display = 'none';
            if (e.target == tabSettingsModal) tabSettingsModal.style.display = 'none';
            if (e.target == deletedTabsModal) deletedTabsModal.style.display = 'none';
            if (e.target == passwordModal) passwordModal.style.display = 'none';
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal(); closeTabModal(); closeTabManageModal(); closeUsersModal(); closeUserModal();
                closeReplyModal(); closeFilesModal(); closeTabUsersModal(); closeTabManageUsersModal();
                closeTabSettingsModal(); closeDeletedTabsModal(); closePasswordModal();
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() { 
            onTitleChange(); 
            onSystemChange();
            onObjectChange();
            loadSystems();
            loadObjects();
        });
    </script>
</body>
</html>
