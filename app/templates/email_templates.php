<?php
// File: app/templates/email_templates.php

/**
 * Email templates for payment recovery
 * These can be customized per organization
 */
return [
    // Standard templates
    'reminder_1' => [
        'subject' => 'Your payment of ${{AMOUNT}} was declined',
        'body' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e5e5; border-radius: 5px;">
                <h2 style="color: #333;">Payment Declined</h2>
                <p>We noticed your recent payment of <strong>${{AMOUNT}}</strong> was declined.</p>
                <p>This can happen for various reasons including insufficient funds, expired card, or incorrect card details.</p>
                <p style="text-align: center; margin: 25px 0;">
                    <a href="{{PAYMENT_LINK}}" style="display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">
                        Complete Payment
                    </a>
                </p>
                <p>If you have any questions, please don\'t hesitate to contact our support team.</p>
                <p>Thank you,<br>The Payment Recovery Team</p>
            </div>
        '
    ],
    
    'reminder_2' => [
        'subject' => 'Second Reminder: Your payment of ${{AMOUNT}} is still pending',
        'body' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e5e5; border-radius: 5px;">
                <h2 style="color: #333;">Payment Reminder</h2>
                <p>This is a friendly reminder that your payment of <strong>${{AMOUNT}}</strong> is still pending.</p>
                <p>We understand that things can get busy, and this may have slipped your mind.</p>
                <p style="text-align: center; margin: 25px 0;">
                    <a href="{{PAYMENT_LINK}}" style="display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">
                        Complete Payment Now
                    </a>
                </p>
                <p>If you have any questions or need assistance, please don\'t hesitate to contact our support team.</p>
                <p>Thank you,<br>The Payment Recovery Team</p>
            </div>
        '
    ],
    
    'reminder_3' => [
        'subject' => 'Final Reminder: Your payment of ${{AMOUNT}}',
        'body' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e5e5; border-radius: 5px;">
                <h2 style="color: #333;">Final Payment Reminder</h2>
                <p>This is your final reminder regarding your pending payment of <strong>${{AMOUNT}}</strong>.</p>
                <p>Please complete your payment at your earliest convenience to avoid any service interruptions.</p>
                <p style="text-align: center; margin: 25px 0;">
                    <a href="{{PAYMENT_LINK}}" style="display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">
                        Complete Payment Now
                    </a>
                </p>
                <p>If you have already made this payment or have any questions, please contact our support team.</p>
                <p>Thank you,<br>The Payment Recovery Team</p>
            </div>
        '
    ],
    
    // High value templates
    'reminder_1_high_value' => [
        'subject' => 'Important: Your payment of ${{AMOUNT}} was declined',
        'body' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e5e5; border-radius: 5px;">
                <h2 style="color: #333;">Payment Declined</h2>
                <p>We noticed your recent payment of <strong>${{AMOUNT}}</strong> was declined.</p>
                <p>As a valued customer, we\'ve assigned a dedicated support representative to assist you with this transaction.</p>
                <p style="text-align: center; margin: 25px 0;">
                    <a href="{{PAYMENT_LINK}}" style="display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">
                        Complete Payment
                    </a>
                </p>
                <p>If you prefer assistance by phone, please contact our premium support line at (555) 123-4567.</p>
                <p>Thank you,<br>The Payment Recovery Team</p>
            </div>
        '
    ],
    
    // Medium value templates
    'reminder_1_medium_value' => [
        'subject' => 'Your payment of ${{AMOUNT}} requires attention',
        'body' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e5e5; border-radius: 5px;">
                <h2 style="color: #333;">Payment Attention Required</h2>
                <p>We noticed your recent payment of <strong>${{AMOUNT}}</strong> was declined.</p>
                <p>Please take a moment to update your payment information at your earliest convenience.</p>
                <p style="text-align: center; margin: 25px 0;">
                    <a href="{{PAYMENT_LINK}}" style="display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">
                        Complete Payment
                    </a>
                </p>
                <p>If you have any questions or need assistance, our support team is available to help.</p>
                <p>Thank you,<br>The Payment Recovery Team</p>
            </div>
        '
    ],
    
    // SMS templates
    'reminder_1_sms' => 'Your payment of ${{AMOUNT}} was declined. Please complete your payment here: {{PAYMENT_LINK}}',
    'reminder_2_sms' => 'Reminder: Your payment of ${{AMOUNT}} is still pending. Complete your payment here: {{PAYMENT_LINK}}',
    'reminder_3_sms' => 'Final notice: Please complete your payment of ${{AMOUNT}} here: {{PAYMENT_LINK}}'
];