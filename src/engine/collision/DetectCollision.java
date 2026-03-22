package engine.collision;

import java.util.HashMap;
import java.util.Map;

/**
 * Handles collision detection matrix
 * Determines which layers should check for collisions with each other
 */
public class DetectCollision {
    private Map<String, Map<String, Boolean>> detectionMatrix;
    private boolean defaultDetect;
    
    public DetectCollision() {
        this.detectionMatrix = new HashMap<>();
        this.defaultDetect = true;
    }
    
    /**
     * Set whether two layers should detect collisions
     */
    public void set(String layerA, String layerB, boolean detect) {
        if (!detectionMatrix.containsKey(layerA)) {
            detectionMatrix.put(layerA, new HashMap<>());
        }
        if (!detectionMatrix.containsKey(layerB)) {
            detectionMatrix.put(layerB, new HashMap<>());
        }
        
        detectionMatrix.get(layerA).put(layerB, detect);
        detectionMatrix.get(layerB).put(layerA, detect);
    }
    
    /**
     * Check if two layers should detect collisions
     */
    public boolean shouldDetect(String layerA, String layerB) {
        if (detectionMatrix.containsKey(layerA) && 
            detectionMatrix.get(layerA).containsKey(layerB)) {
            return detectionMatrix.get(layerA).get(layerB);
        }
        return defaultDetect;
    }
    
    /**
     * Set default detection behavior
     */
    public void setDefault(boolean detect) {
        this.defaultDetect = detect;
    }
    
    /**
     * Clear all detection rules
     */
    public void clear() {
        detectionMatrix.clear();
    }
    
    /**
     * Get all layers in the detection matrix
     */
    public java.util.Set<String> getLayers() {
        return detectionMatrix.keySet();
    }
}
