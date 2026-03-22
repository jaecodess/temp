package simulation;

import java.awt.Color;

/**
 * Immutable config bundle for a game level.
 * Holds all tuning values — speeds, score threshold, background colours, title.
 */
public class LevelConfig {
    public final int    levelNumber;
    public final String title;
    public final int    scoreToAdvance;   // score needed to move to next level
    public final float  goodFoodSpeed;
    public final float  badFoodSpeed;
    public final float  badFoodSpeedIncrement; // speed added after each quiz
    // Two colours for the gradient background
    public final Color  bgTop;
    public final Color  bgBottom;
    // Decorative accent colour (grid lines, borders)
    public final Color  accentColor;

    public LevelConfig(int levelNumber, String title, int scoreToAdvance,
                       float goodFoodSpeed, float badFoodSpeed, float badFoodSpeedIncrement,
                       Color bgTop, Color bgBottom, Color accentColor) {
        this.levelNumber          = levelNumber;
        this.title                = title;
        this.scoreToAdvance       = scoreToAdvance;
        this.goodFoodSpeed        = goodFoodSpeed;
        this.badFoodSpeed         = badFoodSpeed;
        this.badFoodSpeedIncrement = badFoodSpeedIncrement;
        this.bgTop                = bgTop;
        this.bgBottom             = bgBottom;
        this.accentColor          = accentColor;
    }
}
