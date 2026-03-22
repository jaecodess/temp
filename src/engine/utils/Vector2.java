package engine.utils;

/**
 * Represents a 2D vector with x and y coordinates
 * Used for positions, velocities, and directions
 */
public class Vector2 {
    public float x;
    public float y;
    
    public Vector2() {
        this(0, 0);
    }
    
    public Vector2(float x, float y) {
        this.x = x;
        this.y = y;
    }
    
    public Vector2(Vector2 other) {
        this.x = other.x;
        this.y = other.y;
    }
    
    public void set(float x, float y) {
        this.x = x;
        this.y = y;
    }
    
    public void set(Vector2 other) {
        this.x = other.x;
        this.y = other.y;
    }
    
    public Vector2 add(Vector2 other) {
        return new Vector2(this.x + other.x, this.y + other.y);
    }
    
    public Vector2 subtract(Vector2 other) {
        return new Vector2(this.x - other.x, this.y - other.y);
    }
    
    public Vector2 multiply(float scalar) {
        return new Vector2(this.x * scalar, this.y * scalar);
    }
    
    public float length() {
        return (float) Math.sqrt(x * x + y * y);
    }
    
    public Vector2 normalize() {
        float len = length();
        if (len != 0) {
            return new Vector2(x / len, y / len);
        }
        return new Vector2(0, 0);
    }
    
    public float distance(Vector2 other) {
        float dx = this.x - other.x;
        float dy = this.y - other.y;
        return (float) Math.sqrt(dx * dx + dy * dy);
    }
    
    @Override
    public String toString() {
        return String.format("Vector2(%.2f, %.2f)", x, y);
    }
    
    @Override
    public boolean equals(Object obj) {
        if (this == obj) return true;
        if (!(obj instanceof Vector2)) return false;
        Vector2 other = (Vector2) obj;
        return Float.compare(x, other.x) == 0 && Float.compare(y, other.y) == 0;
    }
}
