package engine.entities;

/**
 * Player entity - represents a player-controlled character
 * Demonstrates concrete implementation of Entity
 */
public class Player extends Entity {
    private float initialX;
    private float initialY;
    private String name;
    
    public Player(float x, float y) {
        super(x, y);
        this.initialX = x;
        this.initialY = y;
        this.name = "Player";
        this.speed = 200.0f; // pixels per second
        this.width = 32;
        setHeight(32);  // Use setHeight which sets length
    }
    
    public Player(float x, float y, String name) {
        this(x, y);
        this.name = name;
    }
    
    public String getName() {
        return name;
    }
    
    public void setName(String name) {
        this.name = name;
    }
    
    /**
     * Move player in a direction
     */
    public void move(float dx, float dy, float deltaTime) {
        float moveX = dx * speed * deltaTime;
        float moveY = dy * speed * deltaTime;
        setPosition(x + moveX, y + moveY);
        
        // Update state based on movement
        if (Math.abs(dx) > 0 || Math.abs(dy) > 0) {
            setState("moving");
        } else {
            setState("idle");
        }
    }
    
    @Override
    public void resetPosition() {
        setPosition(initialX, initialY);
        velocity.set(0, 0);
        setState("idle");
    }
    
    @Override
    public void update(float deltaTime) {
        // Player-specific update logic
        // In a real game, this might handle animation, buffs, etc.
        if (!active) return;
        
        // Apply velocity
        x += velocity.x * deltaTime;
        y += velocity.y * deltaTime;
        position.set(x, y);
    }
    
    @Override
    public void render() {
        // In a real implementation, this would render the player sprite
        // For simulation, we just track the state
        if (!active) return;
        
        System.out.println(String.format("Rendering %s at (%.1f, %.1f) - State: %s", 
            name, x, y, state));
    }
    
    @Override
    public String toString() {
        return String.format("Player[name=%s, x=%.2f, y=%.2f, state=%s]", 
            name, x, y, state);
    }
}
