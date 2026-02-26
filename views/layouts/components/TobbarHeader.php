<?php
// Fetch Organization Details
$orgDetails = [];
$orgSql = "SELECT organization_name, organization_logo, industry FROM organizations LIMIT 1";
$orgRes = $conn->query($orgSql);
if ($orgRes && $orgRes->num_rows > 0) {
    $orgDetails = $orgRes->fetch_assoc();
}

// Defaults
$orgName = $orgDetails['organization_name'] ?? 'Organization';
$industry  = $orgDetails['industry'] ?? '';
$orgLogo = $orgDetails['organization_logo'] ?? ''; 
$initial = strtoupper(substr($orgName, 0, 1));

// Fetch Unread Notifications
$notifications = [];
$unreadCount = 0;
if (isset($currentUserId)) {
    // Count total unread
    $countSql = "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0 AND is_deleted = 0";
    $stmtC = $conn->prepare($countSql);
    $stmtC->bind_param("i", $currentUserId);
    $stmtC->execute();
    $unreadCount = $stmtC->get_result()->fetch_assoc()['cnt'] ?? 0;
    $stmtC->close();

    // Fetch latest 10 unread
    $notifSql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 AND is_deleted = 0 ORDER BY created_at DESC LIMIT 10";
    $stmtN = $conn->prepare($notifSql);
    $stmtN->bind_param("i", $currentUserId);
    $stmtN->execute();
    $notifRes = $stmtN->get_result();
    while ($row = $notifRes->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmtN->close();
}

function time_elapsed_string($datetime, $full = false) {
    if (!$datetime) return '';
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>

    <!-- Topbar Start -->
        <header class="app-topbar" style="z-index: 999 !important;">
            <div class="container-fluid topbar-menu">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <!-- Topbar Brand Logo -->
                    <div class="logo-topbar">
                        <a href="javascript:void(0);" class="logo-dark">
                            <span class="d-flex align-items-center gap-1">
                                <?php if (!empty($orgLogo)) { ?>
                                <img src="<?= $basePath ?>/uploads/<?= $_SESSION['organization_code'] ?>/organization_logo/<?= $orgLogo ?>" alt="" style="width: 30px;">
                                <?php } else { ?>
                                 <span class="avatar-sm me-2 d-flex align-items-center justify-content-center bg-light text-primary fw-bold rounded-circle" style="width: 40px; height: 40px; min-width: 40px;"><?php echo $initial ?></span>
                                <?php } ?>
                                <span class="logo-text text-body fw-bold fs-xl"><?= htmlspecialchars($orgName) ?></span>
                            </span>
                        </a>
                        <a href="javascript:void(0);" class="logo-light">
                            <span class="d-flex align-items-center gap-1">
                                <?php if (!empty($orgLogo)) { ?>
                                <img src="<?= $basePath ?>/uploads/<?= $_SESSION['organization_code'] ?>/organization_logo/<?= $orgLogo ?>" alt="" style="width: 30px;">
                                <?php } else { ?>
                                 <span class="avatar-sm me-2 d-flex align-items-center justify-content-center bg-light text-primary fw-bold rounded-circle" style="width: 40px; height: 40px; min-width: 40px;"><?php echo $initial ?></span>
                                <?php } ?>
                                <span class="logo-text text-white fw-bold fs-xl"><?= htmlspecialchars($orgName) ?></span>
                            </span>
                        </a>
                    </div>

                    <div class="d-lg-none d-flex mx-1">
                        <a href="javascript:void(0);">
                            <?php if (!empty($orgLogo)) { ?>
                            <img src="<?= $basePath ?>/uploads/<?= $_SESSION['organization_code'] ?>/organization_logo/<?= $orgLogo ?>" height="28" alt="Logo">
                            <?php } else { ?>
                            <span class="avatar-sm me-2 d-flex align-items-center justify-content-center bg-light text-primary fw-bold rounded-circle" style="width: 40px; height: 40px; min-width: 40px;"><?php echo $initial ?></span>
                            <?php } ?>
                        </a>
                    </div>

                    <!-- Sidebar Hover Menu Toggle Button -->
                    <button class="button-collapse-toggle d-xl-none">
                        <i data-lucide="menu" class="fs-22 align-middle"></i>
                    </button>

                    <!-- Topbar Link Item -->
                    <div class="topbar-item d-none d-lg-flex">
                        <a href="#!" class="topbar-link btn shadow-none btn-link px-2 disabled"> v2.0.3</a>
                    </div>

                    <!-- Topbar Link Item -->
                    <?php if (can_access('documentation', 'view')) : ?>
                    <div class="topbar-item d-none d-lg-flex">
                        <a href="<?= $basePath ?>/documentation" class="topbar-link btn shadow-none btn-link px-2"> Documentation</a>
                    </div>
                    <?php endif; ?>

                    <!-- Dropdown -->
                    <!-- <div class="topbar-item">
                        <div class="dropdown">
                            <a href="#!" class="topbar-link btn shadow-none btn-link dropdown-toggle drop-arrow-none px-2"
                               data-bs-toggle="dropdown" data-bs-offset="0,13">
                                Dropdown <i class="ti ti-chevron-down ms-1"></i>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#!">
                                    <i class="ti ti-user-plus fs-15 me-1"></i> Add Project Member
                                </a>
                                <a class="dropdown-item" href="#!">
                                    <i class="ti ti-activity fs-15 me-1"></i> View Activity
                                </a>
                                <a class="dropdown-item" href="#!">
                                    <i class="ti ti-settings fs-15 me-1"></i> Settings
                                </a>
                            </div> 
                        </div>
                    </div>  -->

                    <!-- Mega Menu Dropdown -->
                    <!-- <div class="topbar-item d-none d-md-flex">
                        <div class="dropdown">
                            <button class="topbar-link btn shadow-none btn-link px-2 dropdown-toggle drop-arrow-none"
                                    data-bs-toggle="dropdown" data-bs-offset="0,13" type="button" aria-haspopup="false"
                                    aria-expanded="false">
                                Mega Menu <i class="ti ti-chevron-down ms-1"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-xxl p-0">
                                <div class="h-100" style="max-height: 380px;" data-simplebar>
                                    <div class="row g-0">
                                        <div class="col-md-4">
                                            <div class="p-3">
                                                <h5 class="fw-semibold fs-sm dropdown-header">Workspace Tools</h5>
                                                <ul class="list-unstyled">
                                                    <li><a href="javascript:void(0);" class="dropdown-item">My Dashboard</a>
                                                    </li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Recent Activity</a>
                                                    </li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Notifications
                                                        Center</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">File Manager</a>
                                                    </li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Calendar View</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="p-3">
                                                <h5 class="fw-semibold fs-sm dropdown-header">Team Operations</h5>
                                                <ul class="list-unstyled">
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Team Overview</a>
                                                    </li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Meeting Schedule</a>
                                                    </li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Timesheets</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Feedback Hub</a>
                                                    </li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Resource
                                                        Allocation</a></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="p-3">
                                                <h5 class="fw-semibold fs-sm dropdown-header">Account Settings</h5>
                                                <ul class="list-unstyled">
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Profile Settings</a>
                                                    </li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Billing & Plans</a>
                                                    </li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Integrations</a>
                                                    </li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Privacy &
                                                        Security</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Support Center</a>
                                                    </li>
                                                </ul>
                                            </div> 
                                        </div>
                                    </div> 
                                </div> 
                            </div>
                        </div> 
                    </div>  -->
                </div> 

                <div class="d-flex align-items-center gap-2">
                    <!-- Search -->
                    <!-- <div class="app-search d-none d-xl-flex me-xl-2">
                        <input type="search" class="form-control topbar-search" name="search"
                               placeholder="Search for something...">
                        <i data-lucide="search" class="app-search-icon text-muted"></i>
                    </div> -->

                    <!-- Notification Dropdown -->
  
                    <div class="topbar-item">
                        <div class="dropdown">
                            <button class="topbar-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown"
                                    data-bs-offset="0,19" type="button" data-bs-auto-close="outside" aria-haspopup="false"
                                    aria-expanded="false">
                                <i data-lucide="bell" class="fs-xxl"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="badge badge-square text-bg-danger topbar-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                                <?php endif; ?>
                            </button>

                            <div class="dropdown-menu p-0 dropdown-menu-end dropdown-menu-lg">
                                <div class="px-3 py-2 border-bottom">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h6 class="m-0 fs-md fw-semibold">Notifications</h6>
                                        </div>
                                        <div class="col text-end">
                                            <a href="#!" class="badge text-bg-light badge-label py-1"><?= $unreadCount ?> Unread</a>
                                        </div>
                                    </div>
                                </div>

                                <div style="max-height: 300px;" data-simplebar>
                                    <?php if (empty($notifications)): ?>
                                        <div class="p-4 text-center text-muted">
                                            <i data-lucide="bell-off" class="fs-1 justify-content-center d-flex mx-auto mb-2 opacity-50"></i>
                                            <p class="mb-0">You have no new notifications.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($notifications as $notif): 
                                            // Determine icon/color based on type
                                            $icon = $notif['icon'] ?: 'ti ti-bell';
                                            $iconClass = 'text-primary';
                                            $bgClass = 'bg-primary-subtle';
                                            
                                            switch($notif['type']) {
                                                case 'success': $iconClass = 'text-success'; $bgClass = 'bg-success-subtle'; break;
                                                case 'warning': $iconClass = 'text-warning'; $bgClass = 'bg-warning-subtle'; break;
                                                case 'error':   $iconClass = 'text-danger'; $bgClass = 'bg-danger-subtle'; break;
                                                case 'reminder':$iconClass = 'text-info'; $bgClass = 'bg-info-subtle'; break;
                                            }
                                        ?>
                                        <a href="<?= $basePath ?>/read_notification?id=<?= $notif['id'] ?>" class="dropdown-item notification-item py-2 text-wrap" id="notification-<?= $notif['id'] ?>">
                                            <span class="d-flex gap-2">
                                                <span class="avatar-md flex-shrink-0">
                                                    <span class="avatar-title <?= $bgClass ?> <?= $iconClass ?> rounded-circle fs-22">
                                                        <i class="<?= $icon ?> fs-xl"></i>
                                                    </span>
                                                </span>
                                                <span class="flex-grow-1 text-muted">
                                                    <span class="fw-medium text-body d-block"><?= htmlspecialchars($notif['title']) ?></span>
                                                    <span class="fs-xs text-muted d-block text-truncate" style="max-width: 200px;"><?= strip_tags($notif['message']) ?></span>
                                                    <span class="fs-xs text-muted mt-1 d-block"><i class="ti ti-clock me-1"></i><?= time_elapsed_string($notif['created_at']) ?></span>
                                                </span>
                                            </span>
                                        </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>  

                                <a href="<?= $basePath ?>/notifications"
                                   class="dropdown-item text-center text-reset text-decoration-underline link-offset-2 fw-bold notify-item border-top border-light py-2">
                                    View All Notifications
                                </a>
                            </div>
                        </div>
                    </div>
     

                    <!-- Theme Dropdown -->
                    <div class="topbar-item ">
                        <div class="dropdown" data-dropdown="custom">
                            <button class="topbar-link  fw-semibold" data-bs-toggle="dropdown" data-bs-offset="0,19"
                                    type="button" aria-haspopup="false" aria-expanded="false">
                                <img data-trigger-img src="<?= $basePath ?>/public/assets/images/themes/shadcn.svg" alt="user-image"
                                     class="w-100 rounded  "
                                     height="18">
                                <!-- <span data-trigger-label class="text-nowrap"> Theme </span> -->
                                <!-- <span class="dot-blink" aria-label="live status indicator"></span> -->
                            </button>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-1">
                                <div class="h-100" style="max-height: 250px;" data-simplebar>
                                    <div class="row g-0">
                                        <div class="col-md-6">
                                            <button class="dropdown-item position-relative drop-custom-active"
                                                    data-skin="shadcn">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/shadcn.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Theme</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="corporate">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/corporate.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Corporate</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="spotify">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/spotify.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Spotify</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="saas">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/saas.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">SaaS</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="nature">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/nature.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Nature</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="vintage">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/vintage.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Vintage</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="leafline">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/leafline.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Leafline</span>
                                            </button>
                                        </div>

                                        <div class="col-md-6">
                                            <button class="dropdown-item position-relative" data-skin="ghibli">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/ghibli.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Ghibli</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="slack">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/slack.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Slack</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="material">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/material.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Material Design</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="flat">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/flat.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Flat</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="pastel">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/pastel.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Pastel Pop</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="caffieine">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/caffieine.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Caffieine</span>
                                            </button>

                                            <button class="dropdown-item position-relative" data-skin="redshift">
                                                <img src="<?= $basePath ?>/public/assets/images/themes/redshift.svg" alt="" class="me-1 rounded"
                                                     height="18">
                                                <span class="align-middle">Redshift</span>
                                            </button>
                                        </div>
                                    </div> <!-- end row-->


                                </div> <!-- end .h-100-->
                            </div> <!-- .dropdown-menu-->

                        </div> <!-- end dropdown-->
                    </div> <!-- end topbar item-->
 



                    <!-- Button Trigger Customizer Offcanvas -->
                    <div class="topbar-item d-none d-sm-flex">
                        <button class="topbar-link" data-bs-toggle="offcanvas" data-bs-target="#theme-settings-offcanvas"
                                type="button">
                            <i data-lucide="settings" class="fs-xxl"></i>
                        </button>
                    </div>

                    <!-- Light/Dark Mode Button -->
                    <div class="topbar-item d-none d-sm-flex">
                        <button class="topbar-link" id="light-dark-mode" type="button">
                            <i data-lucide="moon" class="fs-xxl mode-light-moon"></i>
                            <i data-lucide="sun" class="fs-xxl mode-light-sun"></i>
                        </button>
                    </div>

                    <!-- Monochrome Mode Button -->
                    <div class="topbar-item d-none d-sm-flex">
                        <button class="topbar-link" id="monochrome-mode" type="button">
                            <i data-lucide="palette" class="fs-xxl mode-light-moon"></i>
                        </button>
                    </div>

                    <!-- User Dropdown -->
                    <div class="topbar-item nav-user">
                        <div class="dropdown">
                            <a class="topbar-link dropdown-toggle drop-arrow-none px-2" data-bs-toggle="dropdown"
                               data-bs-offset="0,13" href="#!" aria-haspopup="false" aria-expanded="false">
                                <?php 
                                if (!empty($currentUser['employee_image'])) {
                                    $imgSrc = $currentUser['employee_image'];
                                    if (strpos($imgSrc, 'http') !== 0) {
                                        $imgSrc = $basePath . '/uploads/' . $_SESSION['organization_code'] . '/employees/avatars/' . $imgSrc;
                                    }
                                    echo '<img src="' . $imgSrc . '" width="32" class="rounded-circle d-flex" style="width: 32px; height: 32px; object-fit: cover;" alt="user-image" title="' . $currentUser['first_name'] . ' ' . $currentUser['last_name'] . '">';
                                } else {
                                    $initial = strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1));
                                    echo '<div class="avatar-sm d-flex align-items-center justify-content-center bg-light text-primary fw-bold rounded-circle" style="width: 32px; height: 32px; min-width: 32px;">' . $initial . '</div>';
                                }
                                ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <!-- Header -->
                                <div class="dropdown-header noti-title">
                                    <h6 class="text-overflow m-0">Welcome ! <span class="text-primary"><?= htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?></span></h6>
                                    <small class="text-muted"><?= htmlspecialchars($currentUser['role_name'] ?? '') ?></small>
                                </div>

                                <!-- My Profile -->
                                <a href="<?= $basePath ?>/profile" class="dropdown-item">
                                    <i class="ti ti-user-circle me-2 fs-17 align-middle"></i>
                                    <span class="align-middle">Profile</span>
                                </a>

                                <!-- Notifications -->
                                <a href="<?= $basePath ?>/notifications" class="dropdown-item">
                                    <i class="ti ti-bell-ringing me-2 fs-17 align-middle"></i>
                                    <span class="align-middle">Notifications</span>
                                </a>

                                <!-- Wallet -->
                                <!-- <a href="javascript:void(0);" class="dropdown-item">
                                    <i class="ti ti-credit-card me-2 fs-17 align-middle"></i>
                                    <span class="align-middle">Balance: <span class="fw-semibold">$985.25</span></span>
                                </a> -->

                                <!-- Settings -->
                                <a href="<?= $basePath ?>/account_settings" class="dropdown-item">
                                    <i class="ti ti-settings-2 me-2 fs-17 align-middle"></i>
                                    <span class="align-middle">Account Settings</span>
                                </a>

                                <!-- Support -->
                                <!-- <a href="javascript:void(0);" class="dropdown-item">
                                    <i class="ti ti-headset me-2 fs-17 align-middle"></i>
                                    <span class="align-middle">Support Center</span>
                                </a> -->

                                <!-- Divider -->
                                <div class="dropdown-divider"></div>

                                <!-- Lock -->
                                <!-- <a href="auth-lock-screen.html" class="dropdown-item">
                                    <i class="ti ti-lock me-2 fs-17 align-middle"></i>
                                    <span class="align-middle">Lock Screen</span>
                                </a> -->

                                <!-- Logout -->
                                <a href="<?= $basePath ?>/logout" class="dropdown-item text-danger fw-semibold">
                                    <i class="ti ti-logout-2 me-2 fs-17 align-middle"></i>
                                    <span class="align-middle">Log Out</span>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- Topbar End -->

        <script>
            // Skin Dropdown
            document.querySelectorAll('[data-dropdown="custom"]').forEach(dropdown => {
                const trigger = dropdown.querySelector('a[data-bs-toggle="dropdown"], button[data-bs-toggle="dropdown"]');
                const items = dropdown.querySelectorAll('button[data-skin]');

                const triggerImg = trigger.querySelector('[data-trigger-img]');
                const triggerLabel = trigger.querySelector('[data-trigger-label]');

                const config = JSON.parse(JSON.stringify(window.config));
                const currentSkin = config.skin;

                items.forEach(item => {
                    const itemSkin = item.getAttribute('data-skin');
                    const itemImg = item.querySelector('img')?.getAttribute('src');
                    const itemText = item.querySelector('span')?.textContent.trim();

                    // Set active on load
                    if (itemSkin === currentSkin) {
                        item.classList.add('drop-custom-active');
                        if (triggerImg && itemImg) triggerImg.setAttribute('src', itemImg);
                        if (triggerLabel && itemText) triggerLabel.textContent = itemText;
                    } else {
                        item.classList.remove('drop-custom-active');
                    }

                    // Click handler
                    item.addEventListener('click', function () {
                        items.forEach(i => i.classList.remove('drop-custom-active'));
                        this.classList.add('drop-custom-active');

                        const newImg = this.querySelector('img')?.getAttribute('src');
                        const newText = this.querySelector('span')?.textContent.trim();

                        if (triggerImg && newImg) triggerImg.setAttribute('src', newImg);
                        if (triggerLabel && newText) triggerLabel.textContent = newText;

                        if (typeof layoutCustomizer !== 'undefined') {
                            layoutCustomizer.changeSkin(itemSkin);
                        }
                    });
                });
            });
        </script>