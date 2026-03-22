package engine.entities;

/**
 * NPC (Non-Player Character) entity
 * Demonstrates AI-controlled entities with behaviors
 */
public class NPC extends Entity {
    private String name;
    private String behaviorType;
    private float patrolRadius;
    private float initialX;
    private float initialY;
    private float patrolTimer;
    private engine.utils.Vector2 targetPosition;
    
    public NPC(float x, float y, String behaviorType) {
        super(x, y);
        this.name = "NPC";
        this.behaviorType = behaviorType;
        this.initialX = x;
        this.initialY = y;
        this.patrolRadius = 200.0f; // Increased from 100 to allow more movement
        this.patrolTimer = 0;
        this.speed = 80.0f; // Increased from 50 to make them move faster
        this.width = 32;
        setHeight(32);
        this.targetPosition = new engine.utils.Vector2(x, y);
    }
    
    public String getBehaviorType() {
        return behaviorType;
    }
    
    public void setBehavior(String type) {
        this.behaviorType = type;
    }
    
    /**
     * Update NPC behavior based on type
     */
    @Override
    public void update(float deltaTime) {
        if (!active) return;
        
        patrolTimer += deltaTime;
        
        switch (behaviorType) {
            case "patrol":
                updatePatrolBehavior(deltaTime);
                break;
            case "idle":
                setState("idle");
                break;
            case "wander":
                updateWanderBehavior(deltaTime);
                break;
            case "follow":
                // Would follow a target entity
                setState("following");
                break;
            default:
                setState("idle");
        }
        
        position.set(x, y);
    }
    
    /**
     * Patrol behavior - move back and forth
     */
    private void updatePatrolBehavior(float deltaTime) {
        setState("patrolling");
        
        // Simple back-and-forth patrol
        float patrolSpeed = speed * deltaTime;
        float offset = (float) Math.sin(patrolTimer) * patrolSpeed;
        
        x = initialX + offset;
        
        // Keep within patrol radius
        if (Math.abs(x - initialX) > patrolRadius) {
            x = initialX + Math.signum(offset) * patrolRadius;
        }
    }
    
    /**
     * Wander behavior - random movement
     */
    private void updateWanderBehavior(float deltaTime) {
        setState("wandering");
        
        // Change direction periodically
        if (patrolTimer % 2.0f < deltaTime) {
            float randomX = (float) (Math.random() - 0.5) * 2;
            float randomY = (float) (Math.random() - 0.5) * 2;
            velocity.set(randomX * speed, randomY * speed);
        }
        
        x += velocity.x * deltaTime;
        y += velocity.y * deltaTime;
        
        // Keep within bounds of patrol radius
        float distFromHome = position.distance(new engine.utils.Vector2(initialX, initialY));
        if (distFromHome > patrolRadius) {
            // Return to home
            float dx = initialX - x;
            float dy = initialY - y;
            float dist = (float) Math.sqrt(dx * dx + dy * dy);
            if (dist > 0) {
                x += (dx / dist) * speed * deltaTime;
                y += (dy / dist) * speed * deltaTime;
            }
        }
    }
    
    @Override
    public void resetPosition() {
        setPosition(initialX, initialY);
        velocity.set(0, 0);
        patrolTimer = 0;
        setState("idle");
    }
    
    @Override
    public void render() {
        if (!active) return;
        
        System.out.println(String.format("Rendering NPC[%s] at (%.1f, %.1f) - State: %s", 
            behaviorType, x, y, state));
    }
    
    /**
     * Set the target position for the NPC
     */
    public void setTarget(float x, float y) {
        targetPosition.set(x, y);
    }
    
    public String getName() {
        return name;
    }
    
    public void setName(String name) {
        this.name = name;
    }
    
    @Override
    public String toString() {
        return String.format("NPC[name=%s, behavior=%s, x=%.2f, y=%.2f, state=%s]", 
            name, behaviorType, x, y, state);
    }
}
