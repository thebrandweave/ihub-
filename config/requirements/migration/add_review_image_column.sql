-- Migration: Add image column to reviews table
-- Run this migration to add image support to reviews

ALTER TABLE reviews 
ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER comment;




