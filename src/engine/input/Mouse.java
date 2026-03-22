package engine.input;

import engine.utils.Vector2;
import java.util.HashMap;
import java.util.Map;

/**
 * Mouse input processor
 * Handles mouse position and button states
 */
public class Mouse extends InputProcessor {
    private boolean[] buttons;
    private int x;
    private int y;
    private Map<Integer, Boolean> buttonStates;
    private Vector2 scrollDelta;
    
    // Common mouse button codes
    public static final int BUTTON_LEFT = 1;
    public static final int BUTTON_RIGHT = 2;
    public static final int BUTTON_MIDDLE = 3;
    
    public Mouse() {
        super();
        this.buttons = new boolean[10];  // Support up to 10 buttons
        this.x = 0;
        this.y = 0;
        this.buttonStates = new HashMap<>();
        this.scrollDelta = new Vector2();
    }
    
    /**
     * Get the X coordinate
     */
    public int getX() {
        return x;
    }
    
    /**
     * Get the Y coordinate
     */
    public int getY() {
        return y;
    }
    
    /**
     * Get current mouse X position (backward compatibility)
     */
    public int getMouseX() {
        return x;
    }
    
    /**
     * Get current mouse Y position (backward compatibility)
     */
    public int getMouseY() {
        return y;
    }
    
    /**
     * Get mouse position as Vector2
     */
    public Vector2 getPosition() {
        return new Vector2(x, y);
    }
    
    /**
     * Set mouse position (for simulation)
     */
    public void setPosition(int x, int y) {
        this.x = x;
        this.y = y;
    }
    
    /**
     * Handle mouse movement
     */
    public void mouseMoved(Vector2 point) {
        this.x = (int)point.x;
        this.y = (int)point.y;
    }
    
    /**
     * Simulate mouse movement (backward compatibility)
     */
    public boolean mouseMoved(int pointX, int pointY) {
        this.x = pointX;
        this.y = pointY;
        return true;
    }
    
    /**
     * Get the button states
     */
    public boolean[] getButtons() {
        return buttons.clone();
    }
    
    /**
     * Handle scroll event
     */
    public void scrolled(float amount) {
        scrollDelta.y = amount;
    }
    
    /**
     * Handle button release
     */
    public void buttonUp(int button) {
        if (button >= 0 && button < buttons.length) {
            buttons[button] = false;
        }
        buttonStates.put(button, false);
    }
    
    /**
     * Handle button press
     */
    public void buttonDown(int button) {
        if (button >= 0 && button < buttons.length) {
            buttons[button] = true;
        }
        buttonStates.put(button, true);
    }
    
    /**
     * Check if a mouse button is pressed
     */
    public boolean isButtonPressed(int button) {
        return buttonStates.getOrDefault(button, false);
    }
    
    /**
     * Set mouse button state (for simulation)
     */
    public void setButtonPressed(int button, boolean pressed) {
        if (button >= 0 && button < buttons.length) {
            buttons[button] = pressed;
        }
        buttonStates.put(button, pressed);
    }
    
    /**
     * Simulate scroll wheel movement (backward compatibility)
     */
    public boolean scrolled(float amountX, float amountY) {
        scrollDelta.set(amountX, amountY);
        return true;
    }
    
    /**
     * Get scroll delta since last poll
     */
    public Vector2 getScrollDelta() {
        return new Vector2(scrollDelta);
    }
    
    @Override
    public void poll() {
        // In a real implementation, this would poll actual mouse state
        // Reset per-frame state
        scrollDelta.set(0, 0);
    }
    
    @Override
    public boolean isPressed(InputAction action) {
        // Mouse typically doesn't directly map to actions
        // but could be extended to do so
        return false;
    }
    
    /**
     * Handle touch down event
     */
    public boolean touchDown(int x, int y, int pointer, int button) {
        this.x = x;
        this.y = y;
        buttonDown(button);
        return true;
    }
    
    /**
     * Handle touch up event
     */
    public boolean touchUp(int x, int y, int pointer, int button) {
        this.x = x;
        this.y = y;
        buttonUp(button);
        return true;
    }
    
    /**
     * Handle touch dragged event
     */
    public boolean touchDragged(int x, int y, int pointer) {
        this.x = x;
        this.y = y;
        return true;
    }
}
