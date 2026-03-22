package engine.input;

/**
 * Abstract class for input processing
 * Different input devices (keyboard, mouse, gamepad) extend this
 */
public abstract class InputProcessor {
    protected InputAction action;
    protected boolean enabled;
    
    public InputProcessor() {
        this.enabled = true;
    }
    
    /**
     * Poll the input device for current state
     */
    public abstract void poll();
    
    /**
     * Check if a specific action is currently pressed
     * @param action The input action to check
     * @return true if the action is pressed
     */
    public abstract boolean isPressed(InputAction action);
    
    /**
     * Process input for a specific action
     */
    public boolean processInput(InputAction action) {
        return isPressed(action);
    }
    
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
}
