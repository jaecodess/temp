package engine.managers;

import engine.input.*;
import engine.output.Speaker;
import java.util.HashMap;
import java.util.Map;

/**
 * Manages all input and output operations
 * Centralizes input device management and action binding
 * Handles audio output through Speaker
 */
public class InputOutputManager {
    private Keyboard keyboard;
    private Mouse mouse;
    private Speaker speaker;
    private Map<InputAction, Integer> bindings;
    private boolean enabled;
    
    public InputOutputManager() {
        this.keyboard = new Keyboard();
        this.mouse = new Mouse();
        this.speaker = new Speaker();
        this.bindings = new HashMap<>();
        this.enabled = true;
    }
    
    /**
     * Poll all input devices
     */
    public void pollInput() {
        if (!enabled) return;
        keyboard.poll();
        mouse.poll();
    }
    
    /**
     * Process all output devices
     */
    public void processOutput() {
        if (!enabled) return;
        speaker.process();
    }
    
    /**
     * Get the keyboard input processor
     */
    public Keyboard getKeyboard() {
        return keyboard;
    }
    
    /**
     * Get the mouse input processor
     */
    public Mouse getMouse() {
        return mouse;
    }
    
    /**
     * Get the speaker output processor
     */
    public Speaker getSpeaker() {
        return speaker;
    }
    
    /**
     * Bind an action to a key code
     */
    public void bindAction(InputAction action, int keyCode) {
        keyboard.bind(action, keyCode);
        bindings.put(action, keyCode);
    }
    
    /**
     * Unbind an action
     */
    public void unbindAction(InputAction action) {
        bindings.remove(action);
    }
    
    /**
     * Process input
     */
    public void processInput() {
        pollInput();
    }
    
    /**
     * Bind an action to a specific key code (backward compatibility)
     */
    public void bind(InputAction action, int keyCode) {
        bindAction(action, keyCode);
    }
    
    /**
     * Check if an action is currently pressed
     */
    public boolean isPressed(InputAction action) {
        return keyboard.isPressed(action);
    }
    
    /**
     * Set multiple bindings at once
     */
    public void setBindings(Map<InputAction, Integer> newBindings) {
        for (Map.Entry<InputAction, Integer> entry : newBindings.entrySet()) {
            bind(entry.getKey(), entry.getValue());
        }
    }
    
    /**
     * Enable or disable input processing
     */
    public void setEnabled(boolean enabled) {
        this.enabled = enabled;
    }
    
    /**
     * Check if input is enabled
     */
    public boolean isEnabled() {
        return enabled;
    }
    
    /**
     * Get current bindings
     */
    public Map<InputAction, Integer> getBindings() {
        return new HashMap<>(bindings);
    }
}
