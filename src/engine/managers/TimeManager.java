package engine.managers;

/**
 * TimeManager handles simulation-wide timing and delta time calculation.
 * This is a non-contextual manager that provides the pulse for the engine.
 */
public class TimeManager {
    private float deltaTime;
    private float totalTime;
    private float timeScale;
    private boolean isPaused;
    private boolean enabled;

    public TimeManager() {
        this.deltaTime = 0;
        this.totalTime = 0;
        this.timeScale = 1.0f;
        this.isPaused = false;
        this.enabled = true;
    }
    
    /**
     * Start the time manager
     */
    public void start() {
        System.out.println("TimeManager: Starting");
        this.isPaused = false;
        this.enabled = true;
    }
    
    /**
     * Resume the time manager
     */
    public void resume() {
        this.isPaused = false;
        System.out.println("TimeManager: Resumed");
    }
    
    /**
     * Pause the time manager
     */
    public void pause() {
        this.isPaused = true;
        System.out.println("TimeManager: Paused");
    }

    /**
     * Updates the simulation clock. 
     * @param dt The raw delta time passed from the system loop (e.g., Gdx.graphics.getDeltaTime()).
     */
    public void update(float dt) {
        if (isPaused) {
            this.deltaTime = 0;
            return;
        }
        this.deltaTime = dt * timeScale;
        this.totalTime += this.deltaTime;
    }

    /**
     * Get the current delta time
     */
    public float getDeltaTime() {
        return deltaTime;
    }
    
    /**
     * Set the delta time
     */
    public void setDeltaTime(float deltaTime) {
        this.deltaTime = deltaTime;
    }
    
    /**
     * Get the total time elapsed
     */
    public float getTotalTime() {
        return totalTime;
    }
    
    /**
     * Get the time scale
     */
    public float getTimeScale() {
        return timeScale;
    }
    
    /**
     * Set the time scale
     */
    public void setTimeScale(float scale) {
        this.timeScale = scale;
    }
    
    /**
     * Check if the time manager is paused
     */
    public boolean isPaused() {
        return isPaused;
    }
    
    /**
     * Update the time manager
     */
    public void update() {
        // Update is handled by update(float dt) method
    }
    
    /**
     * Enable the time manager
     */
    public void enable() {
        this.enabled = true;
    }
    
    /**
     * Disable the time manager
     */
    public void disable() {
        this.enabled = false;
    }
    
    /**
     * Check if enabled
     */
    public boolean isEnabled() {
        return enabled;
    }
    
    /**
     * Dispose of the time manager
     */
    public void dispose() {
        System.out.println("TimeManager: Disposing");
        reset();
    }
    
    public void reset() {
        this.totalTime = 0;
        this.deltaTime = 0;
    }
}