require 'fileutils'

task :default do  
  get_composer  
  run_commands([
    "git pull",
    "export SYMFONY_ENV=prod && ./composer.phar install",
    "rm -Rf app/cache/prod/*",    
    "export SYMFONY_ENV=prod && php app/console cache:warmup"
  ])
end

def get_composer
  if File.file?("composer.phar")
    run_commands([
        "./composer.phar self-update"
    ])
  else
    run_commands([
        "curl -s https://getcomposer.org/installer | php"
    ])
  end
end

def run_commands(commands)
  commands.each do|command|
    puts `#{command}`    
  end   
end