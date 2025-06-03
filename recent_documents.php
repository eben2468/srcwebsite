<?php
/**
 * Recent Documents Component
 * This file displays the most recent documents in a card format
 */

// Include required files if not already included
if (!function_exists('fetchAll')) {
    require_once 'db_config.php';
}

// Get recent documents (limit to 3)
try {
    $recentDocuments = fetchAll("SELECT d.document_id, d.title, d.file_path, d.document_type, 
                                d.created_at, d.file_size, 
                                COALESCE(u.username, 'System') as uploaded_by 
                                FROM documents d 
                                LEFT JOIN users u ON d.uploaded_by = u.user_id 
                                WHERE d.status = 'active' 
                                ORDER BY d.created_at DESC 
                                LIMIT 3");
} catch (Exception $e) {
    // Table might not exist or other error
    $recentDocuments = [];
}

// Function to get appropriate badge color based on document type
function getDocumentBadgeColor($docType) {
    $docType = strtolower($docType);
    switch ($docType) {
        case 'pdf':
            return 'danger';
        case 'doc':
        case 'docx':
            return 'primary';
        case 'xls':
        case 'xlsx':
            return 'success';
        case 'ppt':
        case 'pptx':
            return 'warning';
        case 'txt':
            return 'secondary';
        case 'zip':
        case 'rar':
            return 'info';
        default:
            return 'secondary';
    }
}

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Get document type from file path
function getDocumentType($filePath) {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    return $extension ? strtoupper($extension) : 'Unknown';
}
?>

<!-- Recent Documents Card -->
<div class="content-card animate-fadeIn">
    <div class="content-card-header">
        <h3 class="content-card-title">
            <i class="fas fa-file-alt me-2"></i> Recent Documents
        </h3>
        <a href="documents.php" class="btn btn-sm btn-primary">View All</a>
    </div>
    <div class="content-card-body">
        <?php if (empty($recentDocuments)): ?>
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i> No documents available.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>DOCUMENT</th>
                        <th>TYPE</th>
                        <th>UPLOADED</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentDocuments as $document): ?>
                    <?php 
                        $docType = !empty($document['document_type']) ? 
                            $document['document_type'] : 
                            getDocumentType($document['file_path']);
                        $badgeColor = getDocumentBadgeColor($docType);
                        $formattedDate = date('M j, Y', strtotime($document['created_at']));
                        $fileSize = isset($document['file_size']) ? formatFileSize($document['file_size']) : '';
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="document-icon me-2">
                                    <i class="fas fa-file-<?php echo strtolower($docType) === 'pdf' ? 'pdf' : 
                                        (in_array(strtolower($docType), ['doc', 'docx']) ? 'word' : 
                                        (in_array(strtolower($docType), ['xls', 'xlsx']) ? 'excel' : 
                                        (in_array(strtolower($docType), ['ppt', 'pptx']) ? 'powerpoint' : 'alt'))); ?> 
                                        text-<?php echo $badgeColor; ?>"></i>
                                </div>
                                <div>
                                    <span class="fw-medium"><?php echo htmlspecialchars($document['title']); ?></span>
                                    <?php if ($fileSize): ?>
                                    <small class="text-muted d-block"><?php echo $fileSize; ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $badgeColor; ?>">
                                <?php echo htmlspecialchars($docType); ?>
                            </span>
                        </td>
                        <td><?php echo $formattedDate; ?></td>
                        <td>
                            <a href="document-view.php?id=<?php echo $document['document_id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye me-1"></i> VIEW
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div> 