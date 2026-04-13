<?php
/**
 * Neuromax – Face Match Service
 * 
 * Compares face descriptors using Euclidean distance
 * to find matching users for biometric login.
 */

require_once __DIR__ . '/../models/FaceData.php';

class FaceMatchService
{
    private FaceData $faceDataModel;

    /**
     * Threshold for face match.
     * Lower = stricter matching.
     * 0.6 is standard for face-api.js descriptors.
     */
    private float $threshold = 0.6;

    public function __construct()
    {
        $this->faceDataModel = new FaceData();
    }

    /**
     * Calculate Euclidean distance between two face descriptors.
     *
     * @param array $desc1  First 128-float descriptor
     * @param array $desc2  Second 128-float descriptor
     * @return float        Euclidean distance (0 = identical, typical match < 0.6)
     */
    public function calculateDistance(array $desc1, array $desc2): float
    {
        if (count($desc1) !== count($desc2)) {
            return PHP_FLOAT_MAX; // Incompatible descriptors
        }

        $sum = 0.0;
        for ($i = 0; $i < count($desc1); $i++) {
            $diff = (float)$desc1[$i] - (float)$desc2[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Find the best matching user for a given face descriptor.
     *
     * @param array $descriptor  The 128-float descriptor from face-api.js
     * @return array|null        Matching user data or null if no match
     */
    public function findMatch(array $descriptor): ?array
    {
        $allFaces = $this->faceDataModel->getAll();

        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($allFaces as $face) {
            $stored = json_decode($face['descriptor'], true);
            if (!$stored || !is_array($stored)) continue;

            $distance = $this->calculateDistance($descriptor, $stored);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $face;
            }
        }

        // Only return if distance is below threshold
        if ($bestDistance < $this->threshold && $bestMatch) {
            return [
                'user_id'  => (int)$bestMatch['user_id'],
                'name'     => $bestMatch['name'],
                'email'    => $bestMatch['email'],
                'role'     => $bestMatch['role'],
                'distance' => $bestDistance,
            ];
        }

        return null;
    }
}
