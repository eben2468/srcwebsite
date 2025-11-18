<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check for admin status - use unified admin interface check for super admin users
$isAdmin = shouldUseAdminInterface();
if (!$isAdmin) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header("Location: committees.php");
    exit();
}

// Page title
$pageTitle = "Edit Static Committee - SRC Management System";

// Get committee identifier from URL
$committeeId = $_GET['id'] ?? '';
if (empty($committeeId)) {
    $_SESSION['error'] = "No committee identifier provided.";
    header("Location: committees.php");
    exit();
}

// Map committee identifiers to committee names and types
$staticCommittees = [
    'electoral' => ['name' => 'Electoral Commission', 'type' => 'Ad Hoc'],
    'vetting' => ['name' => 'Vetting Committee', 'type' => 'Ad Hoc'],
    'constitutional' => ['name' => 'Constitutional Amendment Committee', 'type' => 'Ad Hoc'],
    'finance' => ['name' => 'Finance Committee', 'type' => 'Standing'],
    'welfare' => ['name' => 'Welfare Committee', 'type' => 'Standing'],
    'presidents' => ['name' => "President's Committee", 'type' => 'Standing'],
    'publicity' => ['name' => 'Public Relations Committee', 'type' => 'Standing'],
    'organizing' => ['name' => 'Organizing Committee', 'type' => 'Standing'],
    'judicial' => ['name' => 'Judicial Committee', 'type' => 'Standing'],
    'audit' => ['name' => 'Audit Board', 'type' => 'Standing'],
    'editorial' => ['name' => 'Editorial Committee', 'type' => 'Standing'],
    'academic' => ['name' => 'Academic Affairs Committee', 'type' => 'Standing'],
    'sports' => ['name' => 'Sports and Games Committee', 'type' => 'Standing'],
    'entertainment' => ['name' => 'Entertainment Committee', 'type' => 'Standing'],
    'security' => ['name' => 'Security Committee', 'type' => 'Standing'],
    'catering' => ['name' => 'Catering Services Committee', 'type' => 'Standing']
];

if (!isset($staticCommittees[$committeeId])) {
    $_SESSION['error'] = "Invalid committee identifier.";
    header("Location: committees.php");
    exit();
}

// Get committee details
$committeeName = $staticCommittees[$committeeId]['name'];
$committeeType = $staticCommittees[$committeeId]['type'];

// Try to get committee data from database if it exists (for edited content)
try {
    // Check first in committees table by name
    $sql = "SELECT * FROM committees WHERE name = ?";
    $committee = fetchOne($sql, [$committeeName]);
    
    // If not found, try to get it from committees with a similar name
    if (!$committee) {
        $similarName = str_replace("\'", "'", $committeeName); // Handle escaped apostrophes
        $sql = "SELECT * FROM committees WHERE name LIKE ?";
        $committee = fetchOne($sql, ["%$similarName%"]);
    }
} catch (Exception $e) {
    // Log error but continue with default content
    error_log("Error fetching committee: " . $e->getMessage());
    $committee = null;
}

/**
 * Extract plain text from HTML content
 * 
 * @param string $html The HTML content to extract text from
 * @return string Plain text with each list item on a new line
 */
function extractPlainTextFromHtml($html) {
    if (empty($html)) {
        return '';
    }
    
    // If it doesn't look like HTML, return as is
    if (strpos($html, '<') === false && strpos($html, '>') === false) {
        return $html;
    }
    
    $result = '';
    
    try {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Look for list items
        $lis = $dom->getElementsByTagName('li');
        $items = [];
        
        if ($lis->length > 0) {
            foreach ($lis as $li) {
                // Remove any HTML tags from the list item content
                $items[] = trim(strip_tags($li->textContent));
            }
            $result = implode("\n", $items);
        } else {
            // No list items found, just get the text content
            $result = trim(strip_tags($dom->textContent));
        }
    } catch (Exception $e) {
        // If DOM parsing fails, use regex as fallback
        $text = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "$1\n", $html);
        $result = trim(strip_tags($text));
    }
    
    return $result;
}

// If committee data was found, populate default values for the form
if ($committee) {
    // Get the base content for displaying if DB content is missing parts
    $defaultContent = getStaticCommitteeDefaults($committeeId);
    
    // Prefill the purpose from the database
    $purpose = !empty($committee['purpose']) ? $committee['purpose'] : $defaultContent['purpose'] ?? '';
    
    // Extract plain text for editing
    $composition = extractPlainTextFromHtml($committee['composition'] ?? '');
    $responsibilities = extractPlainTextFromHtml($committee['responsibilities'] ?? '');
    
    // If empty, try to use default content
    if (empty($composition) && !empty($defaultContent['composition'])) {
        $composition = extractPlainTextFromHtml($defaultContent['composition']);
    }
    
    if (empty($responsibilities) && !empty($defaultContent['responsibilities'])) {
        $responsibilities = extractPlainTextFromHtml($defaultContent['responsibilities']);
    }
    
    // Store raw HTML versions for hidden fields
    $compositionHtml = $committee['composition'] ?? '';
    $responsibilitiesHtml = $committee['responsibilities'] ?? '';
    
    // Prefill description from the database
    $description = $committee['description'] ?? '';
} else {
    // No committee found, use defaults
    $defaultContent = getStaticCommitteeDefaults($committeeId);
    
    $purpose = $defaultContent['purpose'] ?? '';
    $composition = extractPlainTextFromHtml($defaultContent['composition'] ?? '');
    $responsibilities = extractPlainTextFromHtml($defaultContent['responsibilities'] ?? '');
    $compositionHtml = $defaultContent['composition'] ?? '';
    $responsibilitiesHtml = $defaultContent['responsibilities'] ?? '';
    $description = '';
}

/**
 * Get default content for static committees
 * 
 * @param string $committeeId The identifier of the committee
 * @return array Default content for the committee
 */
function getStaticCommitteeDefaults($committeeId) {
    $defaults = [
        'welfare' => [
            'purpose' => 'Focuses on student wellbeing, health, accommodation, and general welfare issues.',
            'composition' => '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Vice-President (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer</li><li><i class="fas fa-check-circle me-2 text-success"></i>Welfare Officer</li><li><i class="fas fa-check-circle me-2 text-success"></i>Representatives from various halls on campus</li><li><i class="fas fa-check-circle me-2 text-success"></i>Two off-campus representatives</li><li><i class="fas fa-check-circle me-2 text-success"></i>Representatives from distance, summer, sandwich and evening modes</li></ul>',
            'responsibilities' => '<ul><li>Address all facets of students\' wellbeing</li><li>Handle matters related to sickness and health</li><li>Support during bereavement and marriage</li><li>Ensure students\' security both in and outside campus</li><li>Develop policies for welfare donations</li></ul>'
        ],
        'finance' => [
            'purpose' => 'Manages SRC finances, budgeting, and financial planning.',
            'composition' => '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer, main campus (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer, Techiman campus</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer, Kumasi campus</li><li><i class="fas fa-check-circle me-2 text-success"></i>President from the School of Business Student\'s Association</li><li><i class="fas fa-check-circle me-2 text-success"></i>Senate President</li><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty Advisor (Financial Administration)</li></ul>',
            'responsibilities' => '<ul><li>Prepare and present a budget to the Administrative Committee</li><li>Coordinate with Finance Officers of each Campus</li><li>Raise funds for the Council</li><li>Draw policies regarding financial discipline</li><li>Review existing financial policies</li></ul>'
        ],
        'presidents' => [
            'purpose' => 'Brings together leadership from all campus organizations to develop plans for collective interest.',
            'composition' => '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>SRC President (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>SRC Vice-President</li><li><i class="fas fa-check-circle me-2 text-success"></i>SRC Secretary</li><li><i class="fas fa-check-circle me-2 text-success"></i>All Presidents of other modes</li><li><i class="fas fa-check-circle me-2 text-success"></i>All Presidents of Clubs and Associations</li></ul>',
            'responsibilities' => '<ul><li>Develop insightful plans from departmental perspectives</li><li>Work for the collective interest of the Council</li><li>Meet at least twice per semester</li><li>Coordinate activities across all campus organizations</li></ul>'
        ],
        'publicity' => [
            'purpose' => 'Handles publicity and promotes the image of the Council.',
            'composition' => '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Public Relations Officer of SRC (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer</li><li><i class="fas fa-check-circle me-2 text-success"></i>Organizing Secretary</li><li><i class="fas fa-check-circle me-2 text-success"></i>All Public Relations Officers of recognized Clubs, Associations and modes</li></ul>',
            'responsibilities' => '<ul><li>Publicize activities of the Council</li><li>Project the image of the Council in and outside campus</li><li>Manage Council\'s public communications</li><li>Coordinate with media outlets</li></ul>'
        ],
        'organizing' => [
            'purpose' => 'Implements and manages all programs and social activities of the Council.',
            'composition' => '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>SRC Organizing Secretary (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Sports Commissioner</li><li><i class="fas fa-check-circle me-2 text-success"></i>All Organizing Secretaries of Clubs, Associations and modes</li><li><i class="fas fa-check-circle me-2 text-success"></i>Executive Secretary (Secretary)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Public Relations Officer</li></ul>',
            'responsibilities' => '<ul><li>Implement all programs of the Council</li><li>Manage social activities organized by the Council</li><li>Coordinate event logistics</li><li>Ensure successful execution of Council initiatives</li></ul>'
        ],
        'judicial' => [
            'purpose' => 'Enforces and interprets the constitution and handles disputes.',
            'composition' => '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty Advisor (General Administration) (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty Advisor (Financial Administration)</li><li><i class="fas fa-check-circle me-2 text-success"></i>One Senior Hall Assistant from each Hall of Residence</li><li><i class="fas fa-check-circle me-2 text-success"></i>The University Chaplain</li></ul>',
            'responsibilities' => '<ul><li>Enforce and interpret provisions of the constitution</li><li>Address cases of power abuse</li><li>Issue sanctions for constitutional violations</li><li>Resolve disputes within the Council</li></ul>'
        ],
        'audit' => [
            'purpose' => 'Audits the financial records and operations of the Council.',
            'composition' => '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Qualified Auditors (2-4 members based on student population)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Members with minimum B+ in Intermediate Accounting</li></ul>',
            'responsibilities' => '<ul><li>Access and review Council\'s financial books</li><li>Present audit reports to Senate and General Assembly</li><li>Audit the incumbent Administration after elections</li><li>Ensure financial transparency and accountability</li></ul>'
        ],
        'editorial' => [
            'purpose' => 'Responsible for the Council\'s publications and serves as a medium of information and education.',
            'composition' => '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Editor (Chairman)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty Advisors</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer</li><li><i class="fas fa-check-circle me-2 text-success"></i>Executive Secretary (Secretary)</li><li><i class="fas fa-check-circle me-2 text-success"></i>SRC President (ex-officio)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Public Relations Officer (ex-officio)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Three students nominated by the Executive</li></ul>',
            'responsibilities' => '<ul><li>Compile and edit materials for publication</li><li>Forward materials to Senate for approval</li><li>Publish and circulate approved materials</li><li>Serve as a medium of information and education</li><li>Raise funds for the Council through publications</li></ul>'
        ],
        'electoral' => [
            'purpose' => 'Organizes and oversees SRC elections to ensure fairness and transparency.',
            'composition' => '<ul class="mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Chairman/Chairperson (Electoral Commissioner)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Two Deputy Commissioners</li><li><i class="fas fa-check-circle me-2 text-success"></i>Four level representatives (Level 100, 200, 300, and 400)</li></ul>',
            'responsibilities' => '<ul><li>Conduct and supervise General Elections</li><li>Open nominations and receive applications</li><li>Pass applications to the Vetting Committee</li><li>Conduct Senate elections</li><li>Supervise all other Clubs and Associations elections</li></ul>'
        ],
        'vetting' => [
            'purpose' => 'Ensures that candidates for elections satisfy the requirements provided in the Constitution.',
            'composition' => '<ul class="mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Chairman of the Electoral Commission (Chairman)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Two deputies of the Electoral Commission</li><li><i class="fas fa-check-circle me-2 text-success"></i>Dean of Students\' Life and Services</li><li><i class="fas fa-check-circle me-2 text-success"></i>University Chaplain</li><li><i class="fas fa-check-circle me-2 text-success"></i>Dean from each hall of residence</li><li><i class="fas fa-check-circle me-2 text-success"></i>Incumbent officer whose position aspirants are being vetted</li><li><i class="fas fa-check-circle me-2 text-success"></i>President of the Council</li><li><i class="fas fa-check-circle me-2 text-success"></i>Senate President</li><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty member elected to serve on electoral commission</li><li><i class="fas fa-check-circle me-2 text-success"></i>Four members of Electoral Commission (one as Secretary)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Council\'s Advisors for the academic year</li></ul>',
            'responsibilities' => '<ul><li>Vet candidates for elections</li><li>Ensure candidates meet constitutional requirements</li><li>Disqualify candidates who don\'t satisfy requirements</li><li>Evaluate candidates\' performance during vetting</li></ul>'
        ],
        'constitutional' => [
            'purpose' => 'Reviews and proposes amendments to the SRC Constitution.',
            'composition' => '<ul class="mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty Advisor (General Administration) as Chairman</li><li><i class="fas fa-check-circle me-2 text-success"></i>An Editor from the Executive Officers</li><li><i class="fas fa-check-circle me-2 text-success"></i>An English Student (Level 300+ with minimum B+ in specific courses)</li><li><i class="fas fa-check-circle me-2 text-success"></i>A Senate member</li><li><i class="fas fa-check-circle me-2 text-success"></i>Two Executive members from Departmental Associations</li><li><i class="fas fa-check-circle me-2 text-success"></i>Representatives from each mode in the University</li><li><i class="fas fa-check-circle me-2 text-success"></i>A representative from each campus</li></ul>',
            'responsibilities' => '<ul><li>Review proposed amendments</li><li>Publish provisions intended to be amended</li><li>Present draft proposals to the General Assembly</li><li>Present final work to the Senate for ratification</li><li>Ensure other Associations are aware of amendments</li></ul>'
        ]
    ];
    
    return $defaults[$committeeId] ?? [];
}

// Include header
require_once 'includes/header.php';
?>

<style>
.page-header-fix {
    margin-top: 60px;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .page-header-fix {
        margin-top: 3px !important;
    }
}

@media (max-width: 480px) {
    .page-header-fix {
        margin-top: 2px !important;
    }
}

@media (max-width: 375px) {
    .page-header-fix {
        margin-top: 2px !important;
    }
}

@media (max-width: 320px) {
    .page-header-fix {
        margin-top: 2px !important;
    }
}
</style>

<div class="container-fluid px-4 page-header-fix">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-edit me-2"></i>Edit Static Committee</h1>
        <a href="committees.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Committees
        </a>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">
            <h5 class="card-title mb-0">Edit <?php echo htmlspecialchars($committeeName); ?></h5>
        </div>
        <div class="card-body">
            <form action="committees_actions.php" method="post">
                <input type="hidden" name="action" value="edit_static_committee">
                <input type="hidden" name="committee_identifier" value="<?php echo $committeeId; ?>">
                <input type="hidden" name="committee_name" value="<?php echo htmlspecialchars($committeeName); ?>">
                <input type="hidden" name="committee_type" value="<?php echo htmlspecialchars($committeeType); ?>">
                <input type="hidden" name="original_committee_name" value="<?php echo htmlspecialchars($committeeName); ?>">
                
                <!-- Hidden fields to preserve HTML formatting if needed -->
                <input type="hidden" name="committee_composition_html" value="<?php echo htmlspecialchars($compositionHtml ?? ''); ?>">
                <input type="hidden" name="committee_responsibilities_html" value="<?php echo htmlspecialchars($responsibilitiesHtml ?? ''); ?>">
                
                <div class="mb-3">
                    <label for="committee_purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="committee_purpose" name="committee_purpose" rows="2" required><?php 
                        echo htmlspecialchars($purpose); 
                    ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="committee_composition" class="form-label">Composition</label>
                    <textarea class="form-control" id="committee_composition" name="committee_composition" rows="4"><?php 
                        echo htmlspecialchars($composition); 
                    ?></textarea>
                    <small class="form-text text-muted">Enter each member/position on a separate line. HTML formatting will be added automatically.</small>
                </div>
                
                <div class="mb-3">
                    <label for="committee_responsibilities" class="form-label">Key Responsibilities</label>
                    <textarea class="form-control" id="committee_responsibilities" name="committee_responsibilities" rows="4"><?php 
                        echo htmlspecialchars($responsibilities); 
                    ?></textarea>
                    <small class="form-text text-muted">Enter each responsibility on a separate line. HTML formatting will be added automatically.</small>
                </div>
                
                <div class="mb-3">
                    <label for="committee_description" class="form-label">Additional Information (Optional)</label>
                    <textarea class="form-control" id="committee_description" name="committee_description" rows="2"><?php 
                        echo htmlspecialchars($description); 
                    ?></textarea>
                </div>
                
                <div class="border-top pt-3 d-flex justify-content-between">
                    <a href="committees.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the form element
    const form = document.querySelector('form[action="committees_actions.php"]');
    
    // Add submit event listener
    form.addEventListener('submit', function(e) {
        // Let the form submit normally but add a flag to force page refresh
        localStorage.setItem('committee_updated', 'true');
    });
    
    // If we're coming back from a successful update, show a success message
    if (localStorage.getItem('committee_updated') === 'true') {
        // Clear the flag
        localStorage.removeItem('committee_updated');
        
        // Show success message if not already shown by PHP session
        if (!document.querySelector('.alert-success')) {
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> Committee updated successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', alertHtml);
        }
    }
});
</script>
