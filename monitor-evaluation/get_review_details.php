<?php
// Start session and database connection (same as before)

if (isset($_GET['id'])) {
    $review_id = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT pr.*, e.first_name, e.last_name, r.first_name as reviewer_fname, r.last_name as reviewer_lname 
                          FROM performance_reviews pr 
                          JOIN employees e ON pr.employee_id = e.id 
                          JOIN employees r ON pr.reviewer_id = r.id 
                          WHERE pr.id = ?");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $review = $result->fetch_assoc();

    if ($review) {
        $score_class = '';
        if ($review['performance_score'] >= 90) $score_class = 'score-excellent';
        elseif ($review['performance_score'] >= 75) $score_class = 'score-good';
        elseif ($review['performance_score'] >= 60) $score_class = 'score-average';
        else $score_class = 'score-poor';

        echo '<div class="row mb-3">
                <div class="col-md-6">
                    <h6>Employee</h6>
                    <p>' . htmlspecialchars($review['first_name']) . ' ' . htmlspecialchars($review['last_name']) . '</p>
                </div>
                <div class="col-md-6">
                    <h6>Reviewer</h6>
                    <p>' . htmlspecialchars($review['reviewer_fname'] . ' ' . $review['reviewer_lname']) . '</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <h6>Review Date</h6>
                    <p>' . date('M d, Y', strtotime($review['review_date'])) . '</p>
                </div>
                <div class="col-md-4">
                    <h6>Performance Score</h6>
                    <p class="performance-score ' . $score_class . '">' . ($review['performance_score'] ?? 'N/A') . '</p>
                </div>
            </div>
            <div class="mb-3">
                <h6>Strengths</h6>
                <p>' . nl2br(htmlspecialchars($review['strengths'])) . '</p>
            </div>
            <div class="mb-3">
                <h6>Areas for Improvement</h6>
                <p>' . nl2br(htmlspecialchars($review['areas_for_improvement'])) . '</p>
            </div>
            <div class="mb-3">
                <h6>Goals</h6>
                <p>' . nl2br(htmlspecialchars($review['goals'])) . '</p>
            </div>
            <div class="mb-3">
                <h6>Comments</h6>
                <p>' . nl2br(htmlspecialchars($review['comments'])) . '</p>
            </div>';
    } else {
        echo '<div class="alert alert-danger">Review not found</div>';
    }

    $stmt->close();
    $conn->close();
} else {
    echo '<div class="alert alert-danger">Invalid request</div>';
}
