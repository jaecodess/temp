package engine.input;

import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;

/**
 * Keyboard input processor
 * Handles keyboard state and key bindings
 */
public class Keyboard extends InputProcessor {
    private boolean isMuted;
    private Map<Integer, Boolean> keyStates;
    private Map<InputAction, Integer> actionBindings;
    private Set<Integer> pressedKeys;
    
    public Keyboard() {
        super();
        this.keyStates = new HashMap<>();
        this.actionBindings = new HashMap<>();
        this.pressedKeys = new HashSet<>();
        this.isMuted = false;
        initializeDefaultBindings();
    }
    
    /**
     * Initialize default key bindings
     */
    private void initializeDefaultBindings() {
        actionBindings.put(InputAction.MOVE_UP, 87);      // W
        actionBindings.put(InputAction.MOVE_DOWN, 83);    // S
        actionBindings.put(InputAction.MOVE_LEFT, 65);    // A
        actionBindings.put(InputAction.MOVE_RIGHT, 68);   // D
        actionBindings.put(InputAction.ACTION_1, 32);     // Space
        actionBindings.put(InputAction.ACTION_2, 69);     // E
        actionBindings.put(InputAction.PAUSE, 27);        // ESC
        actionBindings.put(InputAction.CONFIRM, 32);      // Space
        actionBindings.put(InputAction.CANCEL, 27);       // ESC
    }
    
    /**
     * Bind an action to a specific key code
     */
    public void bind(InputAction action, int keyCode) {
        actionBindings.put(action, keyCode);
    }
    
    /**
     * Simulate key press (for testing/simulation)
     */
    public void setKeyPressed(int keyCode, boolean pressed) {
        keyStates.put(keyCode, pressed);
        if (pressed) {
            pressedKeys.add(keyCode);
        } else {
            pressedKeys.remove(keyCode);
        }
    }
    
    /**
     * Check if a specific key is pressed
     */
    public boolean isKeyDown(int keyCode) {
        return keyStates.getOrDefault(keyCode, false);
    }
    
    /**
     * Check if a specific key was just pressed this frame
     */
    public boolean isKeyPressed(int keyCode) {
        return pressedKeys.contains(keyCode);
    }
    
    @Override
    public void poll() {
        // In a real implementation, this would poll actual keyboard state
        // For simulation, we maintain state through setKeyPressed
        pressedKeys.clear();
    }
    
    @Override
    public boolean isPressed(InputAction action) {
        Integer keyCode = actionBindings.get(action);
        if (keyCode != null) {
            return isKeyDown(keyCode);
        }
        return false;
    }
    
    /**
     * Get all currently pressed keys
     */
    public Set<Integer> getPressedKeys() {
        Set<Integer> pressed = new HashSet<>();
        for (Map.Entry<Integer, Boolean> entry : keyStates.entrySet()) {
            if (entry.getValue()) {
                pressed.add(entry.getKey());
            }
        }
        return pressed;
    }
    
    /**
     * Handle key up event
     */
    public void keyUp(int keyCode) {
        setKeyPressed(keyCode, false);
    }
    
    public void keyDown(int keyCode) {
        setKeyPressed(keyCode, true);
    }
    
    public void keyTyped(char character) {
        // Handle character input
    }
    
    public void mute() {
        this.isMuted = true;
    }
    
    public void unmute() {
        this.isMuted = false;
    }
    
    public boolean isMuted() {
        return isMuted;
    }
    
    /**
     * Clear all key states
     */
    public void clearAll() {
        keyStates.clear();
        pressedKeys.clear();
    }
}
