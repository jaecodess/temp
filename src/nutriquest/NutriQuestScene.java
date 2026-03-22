package nutriquest;

import engine.entities.Entity;
import engine.entities.Player;
import engine.managers.*;
import engine.collision.ICollisionListener;

import java.util.List;
import java.util.ArrayList;
import java.util.Random;

/**
 * Base scene for NutriQuest meal levels. Each meal level (Breakfast, Lunch, Dinner)
 * extends this and defines which foods spawn. Countdown timer per level.
 */
public abstract class NutriQuestScene extends engine.scene.Scene {
    protected EntityManager entityManager;
    protected MovementManager movementManager;
    protected CollisionManager collisionManager;
    protected InputOutputManager inputManager;
    protected Player player;
    protected NutritionTracker nutritionTracker;
    protected QuizManager quizManager;
    protected List<Food> foods;
    protected float timeRemaining;
    protected float levelDuration;
    protected boolean gameOver;
    protected float worldWidth;
    protected float worldHeight;
    protected Random rnd;

    public NutriQuestScene(String sceneName, EntityManager em, MovementManager mm,
                          CollisionManager cm, InputOutputManager io) {
        super(sceneName);
        this.entityManager = em;
        this.movementManager = mm;
        this.collisionManager = cm;
        this.inputManager = io;
        this.foods = new ArrayList<>();
        this.nutritionTracker = new NutritionTracker();
        this.quizManager = new QuizManager();
        this.rnd = new Random();
        this.worldWidth = 800;
        this.worldHeight = 600;
    }

    /**
     * Define which food IDs spawn for this meal. Override in subclasses.
     */
    protected abstract String[] getFoodSpawns();

    @Override
    public void start() {
        isRunning = true;
        gameOver = false;
        nutritionTracker.reset();
        quizManager = new QuizManager();
        timeRemaining = levelDuration;
        loadAssets();
        spawnFoods();
        setupCollisions();
    }

    protected void spawnFoods() {
        String[] spawnIds = getFoodSpawns();
        int padding = 80;
        for (String id : spawnIds) {
            float x = padding + rnd.nextFloat() * (worldWidth - 2 * padding);
            float y = padding + rnd.nextFloat() * (worldHeight - 2 * padding);
            Food f = FoodFactory.create(id, x, y);
            f.setWorldBounds(worldWidth, worldHeight);
            foods.add(f);
            entityManager.addEntity(f);
            collisionManager.addCollidable(f, f.isHealthy() ? "green_food" : "red_food");
        }
    }

    protected void setupCollisions() {
        collisionManager.registerHandler("player", "green_food", new ICollisionListener() {
            @Override
            public void onCollision(Entity a, Entity b) {
                Food food = (Food) b;
                nutritionTracker.onFoodCollected(food);
                food.setActive(false);
                entityManager.removeEntity(food);
                inputManager.getSpeaker().beep();
            }
        });
        collisionManager.registerHandler("player", "red_food", new ICollisionListener() {
            @Override
            public void onCollision(Entity a, Entity b) {
                Food food = (Food) b;
                nutritionTracker.onFoodCollected(food);
                if (nutritionTracker.isDead()) {
                    gameOver = true;
                }
                food.setActive(false);
                entityManager.removeEntity(food);
                inputManager.getSpeaker().beep();
            }
        });
    }

    @Override
    public void stop() {
        isRunning = false;
    }

    @Override
    protected void loadAssets() { }

    @Override
    protected void handleInput() {
        if (!isRunning || gameOver) return;
        if (quizManager.isQuizActive()) {
            // Quiz handled by NutriQuestGame UI - keys 1-4 for choices
            return;
        }
    }

    @Override
    protected void draw() {
        if (!isRunning) return;
        System.out.println("\n=== " + getSceneName() + " ===");
        System.out.println("Time: " + String.format("%.1f", timeRemaining) + "s | Score: " +
                nutritionTracker.getScore() + " | Health: " + (int) nutritionTracker.getHealth());
        if (gameOver) System.out.println("*** GAME OVER ***");
        System.out.println("==================\n");
    }

    @Override
    public void update(float dt) {
        if (!isRunning) return;
        if (gameOver) return;

        handleInput();
        nutritionTracker.update(dt);
        timeRemaining -= dt;

        float bonus = quizManager.onTimeUpdate(levelDuration - timeRemaining);
        if (bonus > 0) timeRemaining += bonus;

        if (nutritionTracker.isDead()) {
            gameOver = true;  // Health depleted = game over
        }
        // timeRemaining <= 0 = level complete (handled in NutriQuestGame)

        movementManager.update(dt);
        entityManager.updateAll(dt);
        collisionManager.detectCollisions();
    }

    @Override
    public void render() {
        if (!isRunning) return;
        draw();
    }

    public void setPlayer(Player p) { this.player = p; }
    public Player getPlayer() { return player; }
    public NutritionTracker getNutritionTracker() { return nutritionTracker; }
    public QuizManager getQuizManager() { return quizManager; }
    public boolean isGameOver() { return gameOver; }
    public float getTimeRemaining() { return timeRemaining; }
    public int getScore() { return nutritionTracker.getScore(); }
    public void setWorldSize(float w, float h) { worldWidth = w; worldHeight = h; }
    public void setLevelDuration(float seconds) { levelDuration = seconds; }
    public float getLevelDuration() { return levelDuration; }
    public void addTime(float seconds) { timeRemaining += seconds; }
}
