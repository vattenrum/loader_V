namespace versace_loader.Forms
{
    partial class Main
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.flexForm1 = new FlexForm();
            this.expiration_label = new System.Windows.Forms.Label();
            this.username_label = new System.Windows.Forms.Label();
            this.flex_login_button = new FlexButton();
            this.flexClose1 = new FlexClose();
            this.flexForm1.SuspendLayout();
            this.SuspendLayout();
            // 
            // flexForm1
            // 
            this.flexForm1.Controls.Add(this.expiration_label);
            this.flexForm1.Controls.Add(this.username_label);
            this.flexForm1.Controls.Add(this.flex_login_button);
            this.flexForm1.Controls.Add(this.flexClose1);
            this.flexForm1.Dock = System.Windows.Forms.DockStyle.Fill;
            this.flexForm1.Font = new System.Drawing.Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.flexForm1.Location = new System.Drawing.Point(0, 0);
            this.flexForm1.Name = "flexForm1";
            this.flexForm1.Padding = new System.Windows.Forms.Padding(1, 32, 1, 1);
            this.flexForm1.Size = new System.Drawing.Size(200, 150);
            this.flexForm1.TabIndex = 0;
            this.flexForm1.Text = "VER$ACE - [[USERNAME]]";
            // 
            // expiration_label
            // 
            this.expiration_label.AutoSize = true;
            this.expiration_label.BackColor = System.Drawing.Color.Transparent;
            this.expiration_label.Font = new System.Drawing.Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.expiration_label.Location = new System.Drawing.Point(21, 80);
            this.expiration_label.Name = "expiration_label";
            this.expiration_label.Size = new System.Drawing.Size(17, 20);
            this.expiration_label.TabIndex = 4;
            this.expiration_label.Text = "$";
            // 
            // username_label
            // 
            this.username_label.AutoSize = true;
            this.username_label.BackColor = System.Drawing.Color.Transparent;
            this.username_label.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.username_label.Font = new System.Drawing.Font("Segoe UI Light", 11F);
            this.username_label.Location = new System.Drawing.Point(21, 44);
            this.username_label.Name = "username_label";
            this.username_label.Size = new System.Drawing.Size(17, 20);
            this.username_label.TabIndex = 3;
            this.username_label.Text = "$";
            // 
            // flex_login_button
            // 
            this.flex_login_button.Location = new System.Drawing.Point(25, 116);
            this.flex_login_button.Name = "flex_login_button";
            this.flex_login_button.Size = new System.Drawing.Size(145, 22);
            this.flex_login_button.TabIndex = 2;
            this.flex_login_button.Text = "Inject";
            this.flex_login_button.Click += new System.EventHandler(this.btnInject_Click);
            // 
            // flexClose1
            // 
            this.flexClose1.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.flexClose1.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(246)))), ((int)(((byte)(251)))), ((int)(((byte)(254)))));
            this.flexClose1.Font = new System.Drawing.Font("Marlett", 12F);
            this.flexClose1.Location = new System.Drawing.Point(191, 8);
            this.flexClose1.Name = "flexClose1";
            this.flexClose1.Size = new System.Drawing.Size(18, 18);
            this.flexClose1.TabIndex = 0;
            this.flexClose1.Text = "flexClose1";
            // 
            // Main
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(200, 150);
            this.Controls.Add(this.flexForm1);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.None;
            this.Name = "Main";
            this.Text = "Inject";
            this.TransparencyKey = System.Drawing.Color.Fuchsia;
            this.flexForm1.ResumeLayout(false);
            this.flexForm1.PerformLayout();
            this.ResumeLayout(false);

        }

        #endregion

        private FlexForm flexForm1;
        private FlexClose flexClose1;
        private FlexButton flex_login_button;
        private System.Windows.Forms.Label username_label;
        private System.Windows.Forms.Label expiration_label;
    }
}