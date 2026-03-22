package engine.output;

/**
 * Abstract class for output processing
 * Different output devices (speaker, display) extend this
 */
public abstract class OutputProcessor {
    protected boolean enabled;
    protected float volume;
    
    public OutputProcessor() {
        this.enabled = true;
        this.volume = 1.0f; // Default volume (0.0 to 1.0)
    }
    
    /**
     * Process output
     */
    public abstract void process();
    
    /**
     * Check if processor is enabled
     */
    public boolean isEnabled() {
        return enabled;
    }
    
    /**
     * Enable the processor
     */
    public void enable() {
        this.enabled = true;
    }
    
    /**
     * Disable the processor
     */
    public void disable() {
        this.enabled = false;
    }
    
    /**
     * Get the volume level
     * @return Volume from 0.0 to 1.0
     */
    public float getVolume() {
        return volume;
    }
    
    /**
     * Set the volume level
     * @param volume Volume from 0.0 to 1.0
     */
    public void setVolume(float volume) {
        this.volume = Math.max(0.0f, Math.min(1.0f, volume));
    }
    
    /**
     * Mute the output
     */
    public void mute() {
        this.volume = 0.0f;
    }
    
    /**
     * Unmute the output
     */
    public void unmute() {
        this.volume = 1.0f;
    }
    
    /**
     * Check if output is muted
     */
    public boolean isMuted() {
        return volume <= 0.0f;
    }
}

